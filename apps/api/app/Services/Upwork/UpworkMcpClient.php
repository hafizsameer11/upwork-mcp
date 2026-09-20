<?php

namespace App\Services\Upwork;

use App\Models\McpToolLog;
use App\Models\UpworkAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Thin MCP HTTP client for Upwork's remote MCP server.
 * OAuth tokens are stored per UpworkAccount after browser login / callback.
 */
class UpworkMcpClient
{
    public function __construct(private readonly UpworkAccount $account) {}

    public function listTools(): array
    {
        return $this->rpc('tools/list', []);
    }

    public function callTool(string $name, array $arguments = []): array
    {
        try {
            $result = $this->rpc('tools/call', [
                'name' => $name,
                'arguments' => $arguments,
            ]);

            McpToolLog::query()->create([
                'upwork_account_id' => $this->account->id,
                'tool_name' => $name,
                'arguments' => $arguments,
                'result' => $result,
                'status' => 'ok',
            ]);

            return $result;
        } catch (\Throwable $e) {
            McpToolLog::query()->create([
                'upwork_account_id' => $this->account->id,
                'tool_name' => $name,
                'arguments' => $arguments,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Search jobs via MCP. Tool names may vary; we try common names then fall back.
     */
    public function searchJobs(array $filters): array
    {
        $candidates = [
            'search_jobs',
            'upwork_search_jobs',
            'job_search',
            'searchJobOpenings',
        ];

        foreach ($candidates as $tool) {
            try {
                return $this->callTool($tool, $filters);
            } catch (\Throwable) {
                continue;
            }
        }

        // Dev/demo fallback when MCP tools are unavailable
        return [
            'jobs' => [],
            '_note' => 'No MCP job search tool succeeded. Connect OAuth and verify tool names via listTools().',
        ];
    }

    public function getProfile(): array
    {
        foreach (['get_profile', 'upwork_get_profile', 'freelancer_profile'] as $tool) {
            try {
                return $this->callTool($tool, []);
            } catch (\Throwable) {
                continue;
            }
        }

        return $this->account->profile_snapshot ?? [];
    }

    public function getPortfolio(): array
    {
        foreach (['list_portfolio', 'get_portfolio', 'upwork_list_portfolio'] as $tool) {
            try {
                return $this->callTool($tool, []);
            } catch (\Throwable) {
                continue;
            }
        }

        return ['items' => []];
    }

    public function listMessages(array $args = []): array
    {
        foreach (['list_messages', 'get_messages', 'list_rooms'] as $tool) {
            try {
                return $this->callTool($tool, $args);
            } catch (\Throwable) {
                continue;
            }
        }

        return ['messages' => []];
    }

    /**
     * Draft then confirm proposal. Never call without ApprovalService gate.
     */
    public function submitProposal(array $payload): array
    {
        $draft = null;
        foreach (['submit_proposal', 'upwork_submit_proposal', 'create_proposal'] as $tool) {
            try {
                $draft = $this->callTool($tool, array_merge($payload, ['confirm' => false]));
                break;
            } catch (\Throwable) {
                continue;
            }
        }

        if ($draft === null) {
            throw new RuntimeException('Unable to create Upwork proposal draft via MCP.');
        }

        foreach (['confirm_proposal', 'confirm_write', 'submit_proposal'] as $tool) {
            try {
                return $this->callTool($tool, [
                    'draft_id' => $draft['draft_id'] ?? $draft['id'] ?? null,
                    'confirm' => true,
                    ...$payload,
                ]);
            } catch (\Throwable) {
                continue;
            }
        }

        return $draft;
    }

    private function rpc(string $method, array $params, array $options = []): array
    {
        $token = $this->account->access_token;
        if (! $token) {
            throw new RuntimeException("Upwork account {$this->account->id} is not connected (missing access token).");
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(60)
            ->withHeaders(array_filter([
                'X-Upwork-API-TenantId' => $this->account->tenant_id,
            ]))
            ->post(config('upwork.mcp_url'), [
                'jsonrpc' => '2.0',
                'id' => (string) Str::uuid(),
                'method' => $method,
                'params' => $params,
            ]);

        if ($response->status() === 401) {
            // One refresh attempt, then retry
            if (! ($options['retried'] ?? false) && $this->account->refresh_token) {
                try {
                    app(\App\Services\Upwork\UpworkMcpOAuthService::class)->refresh($this->account->fresh());
                    $this->account->refresh();

                    return $this->rpc($method, $params, ['retried' => true]);
                } catch (\Throwable) {
                    // fall through
                }
            }
            throw new RuntimeException('Upwork MCP unauthorized. Reconnect OAuth for this account.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Upwork MCP error: '.$response->body());
        }

        $json = $response->json();
        if (isset($json['error'])) {
            throw new RuntimeException('Upwork MCP RPC error: '.json_encode($json['error']));
        }

        return $json['result'] ?? $json;
    }
}
