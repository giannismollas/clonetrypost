<?php

declare(strict_types=1);

use App\Enums\SocialAccount\Platform;
use App\Jobs\Inbox\SyncSocialAccountInbox;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Bus;

test('command dispatches SyncSocialAccountInbox for each X account', function () {
    Bus::fake();

    $a1 = SocialAccount::factory()->create([
        'platform' => Platform::X,
        'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access', 'dm.read', 'dm.write', 'tweet.moderate.write'],
    ]);
    $a2 = SocialAccount::factory()->create([
        'platform' => Platform::X,
        'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access', 'dm.read', 'dm.write', 'tweet.moderate.write'],
    ]);
    SocialAccount::factory()->create(['platform' => Platform::Facebook]); // skipped

    $this->artisan('inbox:sync')->assertSuccessful();

    Bus::assertDispatchedTimes(SyncSocialAccountInbox::class, 2);
});

test('command skips X accounts that have not upgraded scopes', function () {
    Bus::fake();

    SocialAccount::factory()->create([
        'platform' => Platform::X,
        'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'], // publishing only
    ]);

    $this->artisan('inbox:sync')->assertSuccessful();

    Bus::assertNotDispatched(SyncSocialAccountInbox::class);
});
