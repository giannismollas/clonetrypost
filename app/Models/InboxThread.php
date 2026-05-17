<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\Status;
use App\Enums\SocialAccount\Platform;
use Database\Factories\InboxThreadFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InboxThread extends Model
{
    /** @use HasFactory<InboxThreadFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id',
        'social_account_id',
        'post_platform_id',
        'platform',
        'kind',
        'external_thread_id',
        'participant_handle',
        'participant_avatar',
        'last_message_at',
        'last_user_message_at',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'kind' => Kind::class,
            'status' => Status::class,
            'last_message_at' => 'datetime',
            'last_user_message_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function postPlatform(): BelongsTo
    {
        return $this->belongsTo(PostPlatform::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InboxMessage::class, 'thread_id');
    }
}
