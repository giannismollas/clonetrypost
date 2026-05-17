<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Inbox\Kind;
use App\Enums\SocialAccount\Platform;
use Database\Factories\InboxSyncStateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxSyncState extends Model
{
    /** @use HasFactory<InboxSyncStateFactory> */
    use HasFactory, HasUuids;

    protected $table = 'inbox_sync_state';

    protected $fillable = [
        'social_account_id',
        'platform',
        'kind',
        'last_synced_at',
        'last_cursor',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'kind' => Kind::class,
            'last_synced_at' => 'datetime',
        ];
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }
}
