<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Inbox\SendReplyRequest;
use App\Http\Resources\App\InboxThreadResource;
use App\Models\InboxThread;
use App\Services\Inbox\InboxProviderRegistry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InboxThreadController extends Controller
{
    public function show(Request $request, InboxThread $thread): InboxThreadResource
    {
        $this->authorizeWorkspace($request, $thread);

        $thread->load(['messages' => fn ($q) => $q->orderBy('posted_at')]);

        return new InboxThreadResource($thread);
    }

    public function reply(SendReplyRequest $request, InboxThread $thread, InboxProviderRegistry $registry): Response
    {
        $this->authorizeWorkspace($request, $thread);

        $provider = $registry->for($thread->platform);

        match ($thread->kind) {
            Kind::Dm => $provider->sendDm($thread, $request->validated('body')),
            default => $provider->reply($thread, $request->validated('body')),
        };

        return response()->noContent();
    }

    public function hide(Request $request, InboxThread $thread, InboxProviderRegistry $registry): Response
    {
        $this->authorizeWorkspace($request, $thread);

        $registry->for($thread->platform)->hideReply($thread);

        return response()->noContent();
    }

    public function update(Request $request, InboxThread $thread): Response
    {
        $this->authorizeWorkspace($request, $thread);

        $status = Status::tryFrom($request->string('status')->toString());
        abort_if($status === null, Response::HTTP_UNPROCESSABLE_ENTITY);

        $thread->update(['status' => $status->value]);

        return response()->noContent();
    }

    private function authorizeWorkspace(Request $request, InboxThread $thread): void
    {
        abort_if(
            $thread->workspace_id !== $request->user()->current_workspace_id,
            Response::HTTP_FORBIDDEN,
        );
    }
}
