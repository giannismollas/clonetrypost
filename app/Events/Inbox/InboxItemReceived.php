<?php

declare(strict_types=1);

namespace App\Events\Inbox;

use App\Models\InboxThread;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class InboxItemReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public InboxThread $thread) {}

    public function broadcastAs(): string
    {
        return 'inbox.item.received';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("workspace.{$this->thread->workspace_id}.inbox"),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->thread->id,
            'platform' => $this->thread->platform->value,
            'kind' => $this->thread->kind->value,
        ];
    }

    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }
}
