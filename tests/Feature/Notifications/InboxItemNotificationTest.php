<?php

declare(strict_types=1);

use App\Events\Inbox\InboxItemReceived;
use App\Models\InboxThread;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\InboxItemNotification;
use Illuminate\Support\Facades\Notification;

test('event dispatches InboxItemNotification to workspace owner', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
    $thread = InboxThread::factory()->create(['workspace_id' => $workspace->id]);

    InboxItemReceived::dispatch($thread);

    Notification::assertSentTo($owner, InboxItemNotification::class);
});
