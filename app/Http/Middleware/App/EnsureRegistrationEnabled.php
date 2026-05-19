<?php

declare(strict_types=1);

namespace App\Http\Middleware\App;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureRegistrationEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! config('trypost.self_hosted')) {
            return $next($request);
        }

        if ($inviteId = $request->query('invite') ?? $request->session()->get('pending_invite_id')) {
            $request->session()->put('pending_invite_id', $inviteId);

            return $next($request);
        }

        throw new NotFoundHttpException;
    }
}
