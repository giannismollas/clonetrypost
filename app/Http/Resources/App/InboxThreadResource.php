<?php

declare(strict_types=1);

namespace App\Http\Resources\App;

use App\Models\InboxThread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InboxThread */
class InboxThreadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform->value,
            'kind' => $this->kind->value,
            'status' => $this->status->value,
            'participant_handle' => $this->participant_handle,
            'participant_avatar' => $this->participant_avatar,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'last_user_message_at' => $this->last_user_message_at?->toIso8601String(),
            'social_account_id' => $this->social_account_id,
            'messages' => InboxMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
