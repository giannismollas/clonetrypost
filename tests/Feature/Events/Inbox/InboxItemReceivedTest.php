<?php

declare(strict_types=1);

use App\Events\Inbox\InboxItemReceived;
use App\Models\InboxThread;
use Illuminate\Broadcasting\PrivateChannel;

test('event broadcasts on the workspace inbox channel', function () {
    $thread = InboxThread::factory()->create();

    $event = new InboxItemReceived($thread);

    $channels = $event->broadcastOn();

    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
    expect($channels[0]->name)->toBe("private-workspace.{$thread->workspace_id}.inbox");
});

test('event payload contains thread id and platform', function () {
    $thread = InboxThread::factory()->create();
    $event = new InboxItemReceived($thread);

    expect($event->broadcastWith())->toMatchArray([
        'thread_id' => $thread->id,
        'platform' => $thread->platform->value,
        'kind' => $thread->kind->value,
    ]);
});
