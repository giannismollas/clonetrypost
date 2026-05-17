<?php

declare(strict_types=1);

use App\Enums\Notification\Channel;
use App\Enums\Notification\Type;
use App\Events\Inbox\InboxItemReceived;
use App\Jobs\SendNotification;
use App\Models\InboxThread;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Bus;

test('event dispatches SendNotification to workspace owner with InboxItemReceived type', function () {
    Bus::fake();

    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
    $thread = InboxThread::factory()->create(['workspace_id' => $workspace->id]);

    InboxItemReceived::dispatch($thread);

    Bus::assertDispatched(SendNotification::class, function (SendNotification $job) use ($owner, $thread) {
        return $job->user->id === $owner->id
            && $job->workspaceId === $thread->workspace_id
            && $job->type === Type::InboxItemReceived
            && $job->channel === Channel::InApp;
    });
});
