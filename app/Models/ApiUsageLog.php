<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SocialAccount\Platform;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLog extends Model
{
    use HasUuids;

    protected $table = 'api_usage_log';

    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'social_account_id',
        'platform',
        'endpoint',
        'cost_usd',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'cost_usd' => 'decimal:6',
            'occurred_at' => 'datetime',
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
}
