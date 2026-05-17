<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\Status;
use App\Enums\SocialAccount\Platform;
use App\Http\Controllers\Controller;
use App\Http\Resources\App\InboxThreadResource;
use App\Models\InboxThread;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $query = InboxThread::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('last_message_at');

        if ($platform = $request->string('platform')->toString()) {
            if ($enum = Platform::tryFrom($platform)) {
                $query->where('platform', $enum);
            }
        }

        if ($kind = $request->string('kind')->toString()) {
            if ($enum = Kind::tryFrom($kind)) {
                $query->where('kind', $enum);
            }
        }

        if ($status = $request->string('status')->toString()) {
            if ($enum = Status::tryFrom($status)) {
                $query->where('status', $enum);
            }
        }

        $xAccountsNeedingUpgrade = $workspace->socialAccounts()
            ->active()
            ->where('platform', Platform::X)
            ->get()
            ->filter(fn ($a) => $a->requiresInboxScopeUpgrade())
            ->map(fn ($a) => ['id' => $a->id, 'username' => $a->username])
            ->values();

        return Inertia::render('inbox/Index', [
            'threads' => Inertia::scroll(fn () => InboxThreadResource::collection(
                $query->paginate(25)
            )),
            'filters' => [
                'platform' => $request->string('platform')->toString(),
                'kind' => $request->string('kind')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'x_accounts_needing_upgrade' => $xAccountsNeedingUpgrade,
        ]);
    }
}
