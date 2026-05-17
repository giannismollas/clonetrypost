<?php

declare(strict_types=1);

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\MessageDirection;
use App\Enums\SocialAccount\Platform;
use App\Models\ApiUsageLog;
use App\Models\InboxMessage;
use App\Models\InboxSyncState;
use App\Models\InboxThread;
use App\Models\SocialAccount;
use App\Services\Inbox\XInboxProvider;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    $this->account = SocialAccount::factory()->create([
        'platform' => Platform::X,
        'access_token' => 'fake-token',
        'platform_user_id' => '12345',
    ]);
});

test('syncMentions creates threads and messages from X mentions endpoint', function () {
    Http::fake([
        'api.x.com/2/users/12345/mentions*' => Http::response([
            'data' => [
                [
                    'id' => '999',
                    'text' => '@you nice post',
                    'author_id' => 'a1',
                    'conversation_id' => '999',
                    'created_at' => '2026-05-17T10:00:00Z',
                ],
            ],
            'includes' => [
                'users' => [
                    ['id' => 'a1', 'username' => 'alice', 'profile_image_url' => 'https://x.com/alice.jpg'],
                ],
            ],
            'meta' => ['newest_id' => '999'],
        ], 200),
    ]);

    $count = app(XInboxProvider::class)->syncMentions($this->account);

    expect($count)->toBe(1);

    $thread = InboxThread::query()->first();
    expect($thread)->not->toBeNull();
    expect($thread->platform)->toBe(Platform::X);
    expect($thread->kind)->toBe(Kind::Mention);
    expect($thread->external_thread_id)->toBe('999');
    expect($thread->participant_handle)->toBe('@alice');

    $message = InboxMessage::query()->first();
    expect($message->thread_id)->toBe($thread->id);
    expect($message->external_message_id)->toBe('999');
    expect($message->direction)->toBe(MessageDirection::Inbound);
    expect($message->body)->toBe('@you nice post');
});

test('syncMentions uses since_id from sync_state on subsequent runs', function () {
    InboxSyncState::factory()->create([
        'social_account_id' => $this->account->id,
        'platform' => Platform::X,
        'kind' => Kind::Mention,
        'last_cursor' => '500',
    ]);

    Http::fake([
        'api.x.com/2/users/12345/mentions*' => Http::response(['data' => [], 'meta' => []], 200),
    ]);

    app(XInboxProvider::class)->syncMentions($this->account);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'since_id=500');
    });
});

test('syncMentions deduplicates threads by external_thread_id', function () {
    InboxThread::factory()->create([
        'social_account_id' => $this->account->id,
        'platform' => Platform::X,
        'kind' => Kind::Mention,
        'external_thread_id' => '999',
    ]);

    Http::fake([
        'api.x.com/2/users/12345/mentions*' => Http::response([
            'data' => [
                ['id' => '999', 'text' => 'updated', 'author_id' => 'a1', 'conversation_id' => '999', 'created_at' => '2026-05-17T10:00:00Z'],
            ],
            'includes' => ['users' => [['id' => 'a1', 'username' => 'alice']]],
            'meta' => ['newest_id' => '999'],
        ], 200),
    ]);

    app(XInboxProvider::class)->syncMentions($this->account);

    expect(InboxThread::query()->count())->toBe(1);
});

test('syncMentions logs cost per request', function () {
    Http::fake([
        'api.x.com/2/users/12345/mentions*' => Http::response(['data' => [], 'meta' => []], 200),
    ]);

    app(XInboxProvider::class)->syncMentions($this->account);

    expect(ApiUsageLog::query()->count())->toBe(1);
    expect((float) ApiUsageLog::query()->first()->cost_usd)->toBe(0.005);
});

test('syncDms creates threads + messages for incoming DMs', function () {
    Http::fake([
        'api.x.com/2/dm_events*' => Http::response([
            'data' => [
                [
                    'id' => 'm1',
                    'event_type' => 'MessageCreate',
                    'dm_conversation_id' => 'conv-1',
                    'sender_id' => 'a1',
                    'text' => 'hi there',
                    'created_at' => '2026-05-17T10:00:00Z',
                ],
            ],
            'includes' => [
                'users' => [['id' => 'a1', 'username' => 'alice', 'profile_image_url' => 'https://x.com/a.jpg']],
            ],
            'meta' => ['newest_id' => 'm1'],
        ], 200),
    ]);

    $count = app(XInboxProvider::class)->syncDms($this->account);

    expect($count)->toBe(1);

    $thread = InboxThread::query()->first();
    expect($thread->kind)->toBe(Kind::Dm);
    expect($thread->external_thread_id)->toBe('conv-1');
    expect($thread->participant_handle)->toBe('@alice');
});

test('syncDms marks outbound messages (sent by us) with author_is_us = true', function () {
    Http::fake([
        'api.x.com/2/dm_events*' => Http::response([
            'data' => [
                [
                    'id' => 'm2',
                    'event_type' => 'MessageCreate',
                    'dm_conversation_id' => 'conv-2',
                    'sender_id' => '12345',
                    'text' => 'I replied',
                    'created_at' => '2026-05-17T10:00:00Z',
                ],
            ],
            'includes' => ['users' => [['id' => '12345', 'username' => 'me']]],
            'meta' => ['newest_id' => 'm2'],
        ], 200),
    ]);

    app(XInboxProvider::class)->syncDms($this->account);

    $message = InboxMessage::query()->first();
    expect($message->direction)->toBe(MessageDirection::Outbound);
    expect($message->author_is_us)->toBeTrue();
});

test('syncDms logs cost per request', function () {
    Http::fake(['api.x.com/2/dm_events*' => Http::response(['data' => [], 'meta' => []], 200)]);

    app(XInboxProvider::class)->syncDms($this->account);

    $log = ApiUsageLog::query()->first();
    expect($log->endpoint)->toBe('GET /2/dm_events');
    expect((float) $log->cost_usd)->toBe(0.010);
});
