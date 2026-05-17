<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\SocialAccount\Platform as SocialPlatform;
use App\Enums\SocialAccount\Status;
use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class XController extends SocialController
{
    protected string $driver = 'x';

    protected SocialPlatform $platform = SocialPlatform::X;

    protected array $scopes = [
        'tweet.read',
        'tweet.write',
        'users.read',
        'media.write',
        'offline.access',
        'dm.read',
        'dm.write',
        'tweet.moderate.write',
    ];

    public function connect(Request $request): Response|RedirectResponse
    {
        $this->ensurePlatformEnabled();

        $workspace = $request->user()->currentWorkspace;

        if (! $workspace) {
            return redirect()->route('app.workspaces.create');
        }

        $this->authorize('manageAccounts', $workspace);

        return $this->redirectToProvider($request, $this->driver, $this->scopes);
    }

    public function callback(Request $request): View
    {
        if ($upgradeAccountId = $request->session()->pull('x_scope_upgrade_account_id')) {
            return $this->handleScopeUpgradeCallback($request, (string) $upgradeAccountId);
        }

        return $this->handleCallback($request, $this->platform, $this->driver);
    }

    protected function handleScopeUpgradeCallback(Request $request, string $accountId): View
    {
        $account = SocialAccount::query()->find($accountId);

        if (! $account || $account->workspace_id !== $request->user()->current_workspace_id) {
            return $this->popupCallback(false, __('accounts.popup_callback.workspace_not_found'), $this->platform->value);
        }

        try {
            $socialUser = Socialite::driver($this->driver)->user();

            $account->update([
                'access_token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken ?? $account->refresh_token,
                'token_expires_at' => $socialUser->expiresIn
                    ? now()->addSeconds((int) $socialUser->expiresIn)
                    : null,
                'scopes' => $socialUser->approvedScopes ?? $this->scopes,
                'status' => Status::Connected,
                'error_message' => null,
                'disconnected_at' => null,
            ]);

            return $this->popupCallback(true, __('accounts.popup_callback.connected'), $this->platform->value);
        } catch (\Exception $e) {
            Log::error('X scope upgrade OAuth Error', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            return $this->popupCallback(false, __('accounts.popup_callback.error_connecting'), $this->platform->value);
        }
    }
}
