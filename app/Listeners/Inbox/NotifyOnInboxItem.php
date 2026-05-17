<?php

declare(strict_types=1);

namespace App\Listeners\Inbox;

use App\Enums\Notification\Channel;
use App\Enums\Notification\Type;
use App\Events\Inbox\InboxItemReceived;
use App\Jobs\SendNotification;

class NotifyOnInboxItem
{
    public function handle(InboxItemReceived $event): void
    {
        $thread = $event->thread;
        $workspace = $thread->workspace;

        if (! $workspace) {
            return;
        }

        $owner = $workspace->owner;

        if (! $owner) {
            return;
        }

        $kindLabel = match ($thread->kind->value) {
            'dm' => 'a new DM',
            'mention' => 'a mention',
            default => 'a comment',
        };

        $platformLabel = $thread->platform->value;

        SendNotification::dispatch(
            user: $owner,
            workspaceId: $thread->workspace_id,
            type: Type::InboxItemReceived,
            channel: Channel::InApp,
            title: __('notifications.inbox_item_received.title', ['kind' => $kindLabel, 'platform' => $platformLabel]),
            body: __('notifications.inbox_item_received.body', ['handle' => $thread->participant_handle ?? 'someone']),
            data: ['thread_id' => $thread->id, 'kind' => $thread->kind->value, 'platform' => $thread->platform->value],
        );
    }
}
