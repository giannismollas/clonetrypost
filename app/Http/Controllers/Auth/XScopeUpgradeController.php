<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\SocialAccount\Platform;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class XScopeUpgradeController extends XController
{
    public function upgrade(Request $request, SocialAccount $account): SymfonyResponse
    {
        if ($account->platform !== Platform::X) {
            abort(SymfonyResponse::HTTP_NOT_FOUND);
        }

        if ($account->workspace_id !== $request->user()->current_workspace_id) {
            abort(SymfonyResponse::HTTP_FORBIDDEN);
        }

        $request->session()->put('x_scope_upgrade_account_id', $account->id);

        return $this->redirectToProvider($request, $this->driver, $this->scopes);
    }
}
