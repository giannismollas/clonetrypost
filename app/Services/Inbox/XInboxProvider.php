<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Enums\SocialAccount\Platform;
use App\Models\InboxThread;
use App\Models\SocialAccount;
use App\Services\Inbox\Concerns\TracksApiUsage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class XInboxProvider implements InboxProvider
{
    use TracksApiUsage;

    private const COST_POST_READ = 0.005;

    private const COST_USER_READ = 0.010;

    private const COST_DM_READ = 0.010;

    private const COST_WRITE = 0.015;

    private const BASE_URL = 'https://api.x.com/2';

    public function platform(): Platform
    {
        return Platform::X;
    }

    public function syncMentions(SocialAccount $account): int
    {
        throw new \LogicException('not implemented yet');
    }

    public function syncDms(SocialAccount $account): int
    {
        throw new \LogicException('not implemented yet');
    }

    public function reply(InboxThread $thread, string $body): void
    {
        throw new \LogicException('not implemented yet');
    }

    public function sendDm(InboxThread $thread, string $body): void
    {
        throw new \LogicException('not implemented yet');
    }

    public function hideReply(InboxThread $thread): void
    {
        throw new \LogicException('not implemented yet');
    }

    private function authedClient(SocialAccount $account): PendingRequest
    {
        return Http::withToken($account->access_token)
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(5);
    }
}
