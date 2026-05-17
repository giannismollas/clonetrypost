<?php

declare(strict_types=1);

namespace App\Console\Commands\Inbox;

use App\Jobs\Inbox\SyncSocialAccountInbox;
use App\Models\SocialAccount;
use App\Services\Inbox\InboxProviderRegistry;
use Illuminate\Console\Command;

class SyncInboxes extends Command
{
    protected $signature = 'inbox:sync';

    protected $description = 'Dispatch inbox sync jobs for every connected account whose platform has an inbox provider.';

    public function handle(InboxProviderRegistry $registry): int
    {
        SocialAccount::query()
            ->where('is_active', true)
            ->chunkById(100, function ($accounts) use ($registry) {
                foreach ($accounts as $account) {
                    if (! $registry->supports($account->platform)) {
                        continue;
                    }

                    if ($account->requiresInboxScopeUpgrade()) {
                        continue;
                    }

                    SyncSocialAccountInbox::dispatch($account->id);
                }
            });

        return self::SUCCESS;
    }
}
