<?php

declare(strict_types=1);

namespace App\Listeners\Inbox;

use App\Events\Inbox\InboxItemReceived;
use App\Notifications\InboxItemNotification;

class NotifyOnInboxItem
{
    public function handle(InboxItemReceived $event): void
    {
        $owner = $event->thread->workspace?->owner;

        if ($owner) {
            $owner->notify(new InboxItemNotification($event->thread));
        }
    }
}
