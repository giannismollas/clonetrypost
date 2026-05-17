<?php

declare(strict_types=1);

namespace App\Services\Inbox\Concerns;

use App\Models\ApiUsageLog;
use App\Models\SocialAccount;

trait TracksApiUsage
{
    protected function logApiUsage(SocialAccount $account, string $endpoint, float $costUsd): void
    {
        ApiUsageLog::create([
            'workspace_id' => $account->workspace_id,
            'social_account_id' => $account->id,
            'platform' => $account->platform,
            'endpoint' => $endpoint,
            'cost_usd' => $costUsd,
            'occurred_at' => now(),
        ]);
    }
}
