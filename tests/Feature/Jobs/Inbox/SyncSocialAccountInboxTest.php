<?php

declare(strict_types=1);

use App\Enums\SocialAccount\Platform;
use App\Jobs\Inbox\SyncSocialAccountInbox;
use App\Models\SocialAccount;
use App\Services\Inbox\InboxProvider;
use App\Services\Inbox\InboxProviderRegistry;

beforeEach(function () {
    $this->account = SocialAccount::factory()->create(['platform' => Platform::X]);

    $this->provider = mock(InboxProvider::class);
    $this->provider->shouldReceive('platform')->andReturn(Platform::X);

    app()->instance(InboxProviderRegistry::class, new InboxProviderRegistry([$this->provider]));
});

test('job invokes syncMentions and syncDms on the platform provider', function () {
    $this->provider->shouldReceive('syncMentions')->once()->with(Mockery::on(fn ($a) => $a->id === $this->account->id))->andReturn(2);
    $this->provider->shouldReceive('syncDms')->once()->andReturn(1);

    (new SyncSocialAccountInbox($this->account->id))->handle(app(InboxProviderRegistry::class));
});

test('job is a no-op for platforms without an inbox provider', function () {
    $fb = SocialAccount::factory()->create(['platform' => Platform::Facebook]);

    $this->provider->shouldNotReceive('syncMentions');
    $this->provider->shouldNotReceive('syncDms');

    (new SyncSocialAccountInbox($fb->id))->handle(app(InboxProviderRegistry::class));
});

test('job is queued on the inbox queue', function () {
    $job = new SyncSocialAccountInbox($this->account->id);

    expect($job->queue)->toBe('inbox');
});
