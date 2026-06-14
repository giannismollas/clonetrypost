<?php

declare(strict_types=1);

use App\Enums\SocialAccount\Platform;
use App\Models\PostPlatform;
use App\Models\SocialAccount;
use App\Services\Social\TelegramAnalytics;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['trypost.platforms.telegram.bot_token' => 'TESTTOKEN']);
});

it('returns the channel subscriber count as an account metric', function () {
    $account = SocialAccount::factory()->telegram()->create();

    Http::fake([
        '*/botTESTTOKEN/getChatMemberCount*' => Http::response(['ok' => true, 'result' => 1234], 200),
    ]);

    expect(app(TelegramAnalytics::class)->getMetrics($account))
        ->toBe([['label' => 'Subscribers', 'value' => 1234]]);
});

it('returns no account metrics when the member count call fails', function () {
    $account = SocialAccount::factory()->telegram()->create();

    Http::fake([
        '*/botTESTTOKEN/getChatMemberCount*' => Http::response(['ok' => false], 400),
    ]);

    expect(app(TelegramAnalytics::class)->getMetrics($account))->toBe([]);
});

it('maps stored reactions to post metrics', function () {
    $postPlatform = PostPlatform::factory()->create([
        'platform' => Platform::Telegram,
        'meta' => ['reactions' => [['type' => '👍', 'count' => 12], ['type' => '❤️', 'count' => 5]]],
    ]);

    expect(app(TelegramAnalytics::class)->fetchPostMetrics($postPlatform))
        ->toBe([['label' => '👍', 'value' => 12], ['label' => '❤️', 'value' => 5]]);
});

it('returns no post metrics when there are no reactions yet', function () {
    $postPlatform = PostPlatform::factory()->create([
        'platform' => Platform::Telegram,
        'meta' => [],
    ]);

    expect(app(TelegramAnalytics::class)->fetchPostMetrics($postPlatform))->toBe([]);
});
