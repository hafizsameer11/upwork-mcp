<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncUpworkMessages;
use App\Jobs\SyncUpworkPortfolio;
use App\Models\UpworkAccount;
use App\Services\Upwork\UpworkMcpOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpworkOAuthController extends Controller
{
    public function __construct(private readonly UpworkMcpOAuthService $oauth) {}

    public function start(Request $request, UpworkAccount $upworkAccount)
    {
        $result = $this->oauth->beginAuthorization($upworkAccount, $request->user());

        return [
            'authorize_url' => $result['authorize_url'],
            'redirect_uri' => $this->oauth->redirectUri(),
        ];
    }

    public function callback(Request $request)
    {
        $frontend = rtrim((string) config('upwork.frontend_url'), '/');
        $error = $request->string('error')->toString();

        if ($error !== '') {
            $desc = $request->string('error_description')->toString();

            return redirect()->away($frontend.'/accounts?oauth=error&message='.urlencode($desc ?: $error));
        }

        $code = $request->string('code')->toString();
        $state = $request->string('state')->toString();

        if ($code === '' || $state === '') {
            return redirect()->away($frontend.'/accounts?oauth=error&message='.urlencode('Missing OAuth code or state.'));
        }

        try {
            $account = $this->oauth->handleCallback($code, $state);
            SyncUpworkPortfolio::dispatch($account->id);
            SyncUpworkMessages::dispatch($account->id);

            return redirect()->away($frontend.'/accounts?oauth=connected&account='.$account->id);
        } catch (\Throwable $e) {
            Log::warning('Upwork OAuth callback failed', ['error' => $e->getMessage()]);

            return redirect()->away($frontend.'/accounts?oauth=error&message='.urlencode($e->getMessage()));
        }
    }
}
