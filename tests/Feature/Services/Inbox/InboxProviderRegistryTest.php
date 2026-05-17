<?php

declare(strict_types=1);

use App\Enums\SocialAccount\Platform;
use App\Services\Inbox\InboxProvider;
use App\Services\Inbox\InboxProviderRegistry;

test('registry returns provider by platform', function () {
    $provider = mock(InboxProvider::class);
    $provider->shouldReceive('platform')->andReturn(Platform::X);

    $registry = new InboxProviderRegistry([$provider]);

    expect($registry->for(Platform::X))->toBe($provider);
});

test('registry throws when platform has no provider', function () {
    $registry = new InboxProviderRegistry([]);

    expect(fn () => $registry->for(Platform::Facebook))
        ->toThrow(InvalidArgumentException::class);
});

test('registry detects whether a platform is supported', function () {
    $provider = mock(InboxProvider::class);
    $provider->shouldReceive('platform')->andReturn(Platform::X);

    $registry = new InboxProviderRegistry([$provider]);

    expect($registry->supports(Platform::X))->toBeTrue();
    expect($registry->supports(Platform::Facebook))->toBeFalse();
});
