<?php

declare(strict_types=1);

namespace App\Http\Resources\App;

use App\Models\InboxMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InboxMessage */
class InboxMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'direction' => $this->direction->value,
            'author_handle' => $this->author_handle,
            'author_is_us' => $this->author_is_us,
            'body' => $this->body,
            'media' => $this->media,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'was_sent_via_trypost' => $this->was_sent_via_trypost,
        ];
    }
}
