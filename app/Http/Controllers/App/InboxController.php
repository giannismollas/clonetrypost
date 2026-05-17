<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\App\InboxThreadResource;
use App\Models\InboxThread;
use App\Models\PostPlatform;
use App\Services\Inbox\InboxProviderRegistry;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function index(Request $request, InboxProviderRegistry $registry): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $accounts = $workspace->socialAccounts()
            ->active()
            ->get()
            ->filter(fn ($account) => $registry->supports($account->platform))
            ->values();

        $unreadCounts = InboxThread::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', Status::Unread)
            ->selectRaw('social_account_id, COUNT(*) as aggregate')
            ->groupBy('social_account_id')
            ->pluck('aggregate', 'social_account_id');

        $requestedAccountId = $request->string('account')->toString();
        $selectedAccount = $requestedAccountId !== ''
            ? $accounts->firstWhere('id', $requestedAccountId)
            : null;

        if ($selectedAccount === null) {
            $selectedAccount = $accounts->first();
        }

        $accountsResource = $accounts->map(fn ($account) => [
            'id' => $account->id,
            'platform' => $account->platform->value,
            'username' => $account->username,
            'avatar' => $account->avatar_url,
            'unread_count' => (int) ($unreadCounts->get($account->id) ?? 0),
            'requires_inbox_scope_upgrade' => $account->requiresInboxScopeUpgrade(),
        ])->values();

        $kindFilter = $request->string('kind')->toString();
        $statusFilter = $request->string('status')->toString();
        $postFilter = $request->string('post')->toString();

        return Inertia::render('inbox/Index', [
            'accounts' => $accountsResource,
            'selected_account_id' => $selectedAccount?->id,
            'threads' => Inertia::scroll(function () use ($workspace, $selectedAccount, $kindFilter, $statusFilter, $postFilter) {
                if ($postFilter !== '') {
                    $platformIds = PostPlatform::query()
                        ->where('post_id', $postFilter)
                        ->whereHas('socialAccount', fn ($q) => $q->where('workspace_id', $workspace->id))
                        ->pluck('id');

                    $query = InboxThread::query()
                        ->whereIn('post_platform_id', $platformIds)
                        ->orderByDesc('last_message_at');
                } elseif ($selectedAccount === null) {
                    return InboxThreadResource::collection(
                        new LengthAwarePaginator([], 0, 25)
                    );
                } else {
                    $query = InboxThread::query()
                        ->where('workspace_id', $workspace->id)
                        ->where('social_account_id', $selectedAccount->id)
                        ->orderByDesc('last_message_at');
                }

                if ($kindFilter !== '' && ($enum = Kind::tryFrom($kindFilter))) {
                    $query->where('kind', $enum);
                }

                if ($statusFilter !== '' && ($enum = Status::tryFrom($statusFilter))) {
                    $query->where('status', $enum);
                }

                return InboxThreadResource::collection($query->paginate(25));
            }),
            'filters' => [
                'account' => $selectedAccount?->id,
                'kind' => $kindFilter,
                'status' => $statusFilter,
                'post' => $postFilter,
            ],
        ]);
    }
}
