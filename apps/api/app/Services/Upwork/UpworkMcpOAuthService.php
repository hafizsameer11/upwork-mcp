<?php

namespace App\Services\Upwork;

use App\Models\AppSetting;
use App\Models\UpworkAccount;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Upwork MCP OAuth 2.1 (PKCE + Dynamic Client Registration).
 *
 * Discovery: https://mcp.upwork.com/.well-known/oauth-authorization-server
 * Resource:  https://mcp.upwork.com/mcp
 */
class UpworkMcpOAuthService
{
    private const CLIENT_SETTING_KEY = 'upwork_mcp_oauth_client';

    private const STATE_TTL_SECONDS = 900;

    public function metadata(): array
    {
        return Cache::remember('upwork_mcp_oauth_metadata', 3600, function () {
            $response = Http::timeout(20)->acceptJson()
                ->get('https://mcp.upwork.com/.well-known/oauth-authorization-server');

            if (! $response->successful()) {
                throw new RuntimeException('Failed to load Upwork MCP OAuth metadata: '.$response->body());
            }

            return $response->json();
        });
    }

    public function redirectUri(): string
    {
        return rtrim((string) config('upwork.oauth.redirect_uri'), '/');
    }

    /**
     * Register (or reuse) a public OAuth client for this app.
     * Upwork DCR currently accepts loopback redirect URIs (localhost).
     */
    public function ensureClient(): array
    {
        $existing = AppSetting::getValue(self::CLIENT_SETTING_KEY);
        $redirect = $this->redirectUri();

        if (is_array($existing)
            && ! empty($existing['client_id'])
            && in_array($redirect, $existing['redirect_uris'] ?? [], true)
        ) {
            return $existing;
        }

        $meta = $this->metadata();
        $registrationEndpoint = $meta['registration_endpoint'] ?? null;
        if (! $registrationEndpoint) {
            throw new RuntimeException('Upwork OAuth metadata missing registration_endpoint.');
        }

        $response = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->post($registrationEndpoint, [
                'client_name' => config('app.name', 'Upwork BD').' MCP',
                'redirect_uris' => [$redirect],
                'grant_types' => ['authorization_code', 'refresh_token'],
                'response_types' => ['code'],
                'token_endpoint_auth_method' => 'none',
                'client_uri' => config('upwork.frontend_url'),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Upwork OAuth client registration failed: '.$response->body());
        }

        $client = $response->json();
        $client['redirect_uris'] = $client['redirect_uris'] ?? [$redirect];
        AppSetting::setValue(self::CLIENT_SETTING_KEY, $client);

        return $client;
    }

    /**
     * @return array{authorize_url: string, state: string}
     */
    public function beginAuthorization(UpworkAccount $account, User $user): array
    {
        $meta = $this->metadata();
        $client = $this->ensureClient();
        $redirect = $this->redirectUri();

        $state = Str::random(40);
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        Cache::put($this->stateKey($state), [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'code_verifier' => $verifier,
            'redirect_uri' => $redirect,
            'client_id' => $client['client_id'],
        ], self::STATE_TTL_SECONDS);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $redirect,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'state' => $state,
            'resource' => config('upwork.mcp_url'),
        ]);

        return [
            'authorize_url' => ($meta['authorization_endpoint'] ?? '').'?'.$query,
            'state' => $state,
        ];
    }

    public function handleCallback(string $code, string $state): UpworkAccount
    {
        $payload = Cache::pull($this->stateKey($state));
        if (! is_array($payload)) {
            throw new RuntimeException('OAuth state expired or invalid. Start Connect again.');
        }

        $meta = $this->metadata();
        $tokenEndpoint = $meta['token_endpoint'] ?? null;
        if (! $tokenEndpoint) {
            throw new RuntimeException('Upwork OAuth metadata missing token_endpoint.');
        }

        $response = Http::timeout(30)
            ->asForm()
            ->acceptJson()
            ->post($tokenEndpoint, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $payload['redirect_uri'],
                'client_id' => $payload['client_id'],
                'code_verifier' => $payload['code_verifier'],
                'resource' => config('upwork.mcp_url'),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Upwork token exchange failed: '.$response->body());
        }

        $token = $response->json();
        $account = UpworkAccount::query()->findOrFail($payload['account_id']);

        $account->update([
            'access_token' => $token['access_token'] ?? null,
            'refresh_token' => $token['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => isset($token['expires_in'])
                ? now()->addSeconds((int) $token['expires_in'])
                : null,
            'tenant_id' => $token['tenant_id'] ?? $account->tenant_id,
            'upwork_user_id' => $token['user_id'] ?? $token['freelancer_id'] ?? $account->upwork_user_id,
            'is_active' => true,
        ]);

        return $account->fresh();
    }

    public function refresh(UpworkAccount $account): UpworkAccount
    {
        if (! $account->refresh_token) {
            throw new RuntimeException('No refresh token. Reconnect this Upwork account.');
        }

        $meta = $this->metadata();
        $client = $this->ensureClient();
        $tokenEndpoint = $meta['token_endpoint'] ?? null;

        $response = Http::timeout(30)
            ->asForm()
            ->acceptJson()
            ->post($tokenEndpoint, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $account->refresh_token,
                'client_id' => $client['client_id'],
                'resource' => config('upwork.mcp_url'),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Upwork token refresh failed: '.$response->body());
        }

        $token = $response->json();
        $account->update([
            'access_token' => $token['access_token'] ?? $account->access_token,
            'refresh_token' => $token['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => isset($token['expires_in'])
                ? now()->addSeconds((int) $token['expires_in'])
                : $account->token_expires_at,
        ]);

        return $account->fresh();
    }

    private function stateKey(string $state): string
    {
        return 'upwork_oauth_state:'.$state;
    }
}
