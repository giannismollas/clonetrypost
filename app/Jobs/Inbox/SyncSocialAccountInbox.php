<?php

declare(strict_types=1);

namespace App\Jobs\Inbox;

use App\Models\SocialAccount;
use App\Services\Inbox\InboxProviderRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSocialAccountInbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public string $socialAccountId)
    {
        $this->onQueue('inbox');
    }

    public function handle(InboxProviderRegistry $registry): void
    {
        $account = SocialAccount::find($this->socialAccountId);

        if (! $account) {
            return;
        }

        if (! $registry->supports($account->platform)) {
            return;
        }

        $provider = $registry->for($account->platform);

        try {
            $provider->syncMentions($account);
            $provider->syncDms($account);
        } catch (Throwable $e) {
            Log::error('Inbox sync failed', [
                'social_account_id' => $account->id,
                'platform' => $account->platform->value,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
