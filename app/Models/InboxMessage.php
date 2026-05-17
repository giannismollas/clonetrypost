<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Inbox\MessageDirection;
use Database\Factories\InboxMessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxMessage extends Model
{
    /** @use HasFactory<InboxMessageFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'thread_id',
        'external_message_id',
        'direction',
        'author_handle',
        'author_is_us',
        'body',
        'media',
        'reply_to_external_id',
        'posted_at',
        'fetched_at',
        'was_sent_via_trypost',
    ];

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'author_is_us' => 'boolean',
            'media' => 'array',
            'posted_at' => 'datetime',
            'fetched_at' => 'datetime',
            'was_sent_via_trypost' => 'boolean',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(InboxThread::class, 'thread_id');
    }
}
