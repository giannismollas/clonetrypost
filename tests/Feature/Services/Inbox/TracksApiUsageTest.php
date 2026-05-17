<?php

declare(strict_types=1);

use App\Enums\SocialAccount\Platform;
use App\Models\ApiUsageLog;
use App\Models\SocialAccount;
use App\Services\Inbox\Concerns\TracksApiUsage;

class FakeUsageTracker
{
    use TracksApiUsage;

    public function track(SocialAccount $account, string $endpoint, float $cost): void
    {
        $this->logApiUsage($account, $endpoint, $cost);
    }
}

test('logApiUsage writes a row to api_usage_log', function () {
    $account = SocialAccount::factory()->create(['platform' => Platform::X]);

    (new FakeUsageTracker)->track($account, 'GET /2/dm_events', 0.010);

    expect(ApiUsageLog::query()->count())->toBe(1);
    $log = ApiUsageLog::query()->first();
    expect($log->workspace_id)->toBe($account->workspace_id);
    expect($log->social_account_id)->toBe($account->id);
    expect($log->platform)->toBe(Platform::X);
    expect($log->endpoint)->toBe('GET /2/dm_events');
    expect((float) $log->cost_usd)->toBe(0.01);
});
