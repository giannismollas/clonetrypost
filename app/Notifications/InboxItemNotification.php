<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\InboxThread;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InboxItemNotification extends Notification
{
    use Queueable;

    public function __construct(public InboxThread $thread) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'inbox.item.received',
            'thread_id' => $this->thread->id,
            'platform' => $this->thread->platform->value,
            'kind' => $this->thread->kind->value,
            'participant_handle' => $this->thread->participant_handle,
        ];
    }
}
