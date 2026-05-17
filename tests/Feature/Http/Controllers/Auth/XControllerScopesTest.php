<?php

declare(strict_types=1);

use App\Enums\SocialAccount\Platform;
use App\Http\Controllers\Auth\XController;
use App\Models\SocialAccount;

test('XController declares inbox scopes alongside publishing scopes', function () {
    $reflection = new ReflectionClass(XController::class);
    $property = $reflection->getProperty('scopes');
    $scopes = $property->getDefaultValue();

    expect($scopes)->toContain('tweet.read')
        ->toContain('tweet.write')
        ->toContain('users.read')
        ->toContain('offline.access')
        ->toContain('dm.read')
        ->toContain('dm.write')
        ->toContain('tweet.moderate.write');
});

test('SocialAccount::requiresInboxScopeUpgrade returns true for X accounts without inbox scopes', function () {
    $account = SocialAccount::factory()->create([
        'platform' => Platform::X,
        'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
    ]);

    expect($account->requiresInboxScopeUpgrade())->toBeTrue();
});

test('SocialAccount::requiresInboxScopeUpgrade returns false for X accounts with full scopes', function () {
    $account = SocialAccount::factory()->create([
        'platform' => Platform::X,
        'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access', 'dm.read', 'dm.write', 'tweet.moderate.write'],
    ]);

    expect($account->requiresInboxScopeUpgrade())->toBeFalse();
});

test('SocialAccount::requiresInboxScopeUpgrade returns false for non-X platforms', function () {
    $account = SocialAccount::factory()->create([
        'platform' => Platform::Facebook,
        'scopes' => [],
    ]);

    expect($account->requiresInboxScopeUpgrade())->toBeFalse();
});
