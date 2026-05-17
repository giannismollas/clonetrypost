<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\MessageDirection;
use App\Enums\Inbox\Status;
use App\Enums\SocialAccount\Platform;
use App\Events\Inbox\InboxItemReceived;
use App\Models\InboxMessage;
use App\Models\InboxSyncState;
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
        $syncState = $account->inboxSyncStates()
            ->where('platform', Platform::X->value)
            ->where('kind', Kind::Mention->value)
            ->first();

        $query = [
            'expansions' => 'author_id',
            'user.fields' => 'username,profile_image_url',
            'tweet.fields' => 'conversation_id,created_at,author_id',
            'max_results' => 50,
        ];

        if ($syncState?->last_cursor) {
            $query['since_id'] = $syncState->last_cursor;
        }

        $response = $this->authedClient($account)->get(
            self::BASE_URL."/users/{$account->platform_user_id}/mentions",
            $query,
        );

        $this->logApiUsage($account, 'GET /2/users/:id/mentions', self::COST_POST_READ);

        $data = $response->json('data') ?? [];
        $users = collect($response->json('includes.users') ?? [])->keyBy('id');

        foreach ($data as $tweet) {
            $author = $users->get(data_get($tweet, 'author_id'));

            $thread = InboxThread::query()->updateOrCreate(
                [
                    'social_account_id' => $account->id,
                    'platform' => Platform::X->value,
                    'kind' => Kind::Mention->value,
                    'external_thread_id' => data_get($tweet, 'conversation_id'),
                ],
                [
                    'workspace_id' => $account->workspace_id,
                    'participant_handle' => $author ? '@'.data_get($author, 'username') : null,
                    'participant_avatar' => data_get($author, 'profile_image_url'),
                    'last_message_at' => data_get($tweet, 'created_at'),
                    'last_user_message_at' => data_get($tweet, 'created_at'),
                    'status' => Status::Unread->value,
                    'metadata' => ['conversation_id' => data_get($tweet, 'conversation_id')],
                ],
            );

            InboxMessage::query()->updateOrCreate(
                ['thread_id' => $thread->id, 'external_message_id' => data_get($tweet, 'id')],
                [
                    'direction' => MessageDirection::Inbound->value,
                    'author_handle' => $author ? '@'.data_get($author, 'username') : null,
                    'author_is_us' => false,
                    'body' => data_get($tweet, 'text'),
                    'posted_at' => data_get($tweet, 'created_at'),
                    'fetched_at' => now(),
                    'was_sent_via_trypost' => false,
                ],
            );

            if ($thread->wasRecentlyCreated) {
                InboxItemReceived::dispatch($thread);
            }
        }

        $newestId = $response->json('meta.newest_id');
        if ($newestId) {
            InboxSyncState::query()->updateOrCreate(
                [
                    'social_account_id' => $account->id,
                    'platform' => Platform::X->value,
                    'kind' => Kind::Mention->value,
                ],
                ['last_synced_at' => now(), 'last_cursor' => $newestId, 'last_error' => null],
            );
        }

        return count($data);
    }

    public function syncDms(SocialAccount $account): int
    {
        $syncState = $account->inboxSyncStates()
            ->where('platform', Platform::X->value)
            ->where('kind', Kind::Dm->value)
            ->first();

        $query = [
            'event_types' => 'MessageCreate',
            'expansions' => 'sender_id',
            'user.fields' => 'username,profile_image_url',
            'dm_event.fields' => 'sender_id,text,created_at,dm_conversation_id',
            'max_results' => 50,
        ];

        if ($syncState?->last_cursor) {
            $query['pagination_token'] = $syncState->last_cursor;
        }

        $response = $this->authedClient($account)->get(self::BASE_URL.'/dm_events', $query);

        $this->logApiUsage($account, 'GET /2/dm_events', self::COST_DM_READ);

        $data = $response->json('data') ?? [];
        $users = collect($response->json('includes.users') ?? [])->keyBy('id');

        $newestSeen = $syncState?->last_cursor;

        foreach ($data as $event) {
            if (data_get($event, 'event_type') !== 'MessageCreate') {
                continue;
            }

            $senderId = data_get($event, 'sender_id');
            $sender = $users->get($senderId);
            $isUs = $senderId === $account->platform_user_id;

            $thread = InboxThread::query()->updateOrCreate(
                [
                    'social_account_id' => $account->id,
                    'platform' => Platform::X->value,
                    'kind' => Kind::Dm->value,
                    'external_thread_id' => data_get($event, 'dm_conversation_id'),
                ],
                [
                    'workspace_id' => $account->workspace_id,
                    'participant_handle' => $isUs ? null : ($sender ? '@'.data_get($sender, 'username') : null),
                    'participant_avatar' => $isUs ? null : data_get($sender, 'profile_image_url'),
                    'last_message_at' => data_get($event, 'created_at'),
                    'last_user_message_at' => $isUs ? null : data_get($event, 'created_at'),
                    'status' => $isUs ? Status::Read->value : Status::Unread->value,
                    'metadata' => ['dm_conversation_id' => data_get($event, 'dm_conversation_id')],
                ],
            );

            InboxMessage::query()->updateOrCreate(
                ['thread_id' => $thread->id, 'external_message_id' => data_get($event, 'id')],
                [
                    'direction' => $isUs ? MessageDirection::Outbound->value : MessageDirection::Inbound->value,
                    'author_handle' => $sender ? '@'.data_get($sender, 'username') : null,
                    'author_is_us' => $isUs,
                    'body' => data_get($event, 'text'),
                    'posted_at' => data_get($event, 'created_at'),
                    'fetched_at' => now(),
                    'was_sent_via_trypost' => false,
                ],
            );

            if ($thread->wasRecentlyCreated && ! $isUs) {
                InboxItemReceived::dispatch($thread);
            }

            $newestSeen = data_get($event, 'id');
        }

        if ($newestSeen) {
            InboxSyncState::query()->updateOrCreate(
                [
                    'social_account_id' => $account->id,
                    'platform' => Platform::X->value,
                    'kind' => Kind::Dm->value,
                ],
                ['last_synced_at' => now(), 'last_cursor' => $newestSeen, 'last_error' => null],
            );
        }

        return count($data);
    }

    public function reply(InboxThread $thread, string $body): void
    {
        $account = $thread->socialAccount;

        $response = $this->authedClient($account)->post(self::BASE_URL.'/tweets', [
            'text' => $body,
            'reply' => ['in_reply_to_tweet_id' => $thread->external_thread_id],
        ]);

        $this->logApiUsage($account, 'POST /2/tweets', self::COST_WRITE);

        $tweetId = $response->json('data.id');

        InboxMessage::query()->create([
            'thread_id' => $thread->id,
            'external_message_id' => $tweetId,
            'direction' => MessageDirection::Outbound->value,
            'author_handle' => null,
            'author_is_us' => true,
            'body' => $body,
            'posted_at' => now(),
            'fetched_at' => now(),
            'was_sent_via_trypost' => true,
        ]);

        $thread->update([
            'status' => Status::Replied->value,
            'last_message_at' => now(),
        ]);
    }

    public function sendDm(InboxThread $thread, string $body): void
    {
        $account = $thread->socialAccount;

        $response = $this->authedClient($account)->post(
            self::BASE_URL."/dm_conversations/{$thread->external_thread_id}/messages",
            ['text' => $body],
        );

        $this->logApiUsage($account, 'POST /2/dm_conversations/:id/messages', self::COST_WRITE);

        $eventId = $response->json('data.dm_event_id');

        InboxMessage::query()->create([
            'thread_id' => $thread->id,
            'external_message_id' => $eventId,
            'direction' => MessageDirection::Outbound->value,
            'author_handle' => null,
            'author_is_us' => true,
            'body' => $body,
            'posted_at' => now(),
            'fetched_at' => now(),
            'was_sent_via_trypost' => true,
        ]);

        $thread->update([
            'status' => Status::Replied->value,
            'last_message_at' => now(),
        ]);
    }

    public function hideReply(InboxThread $thread): void
    {
        $account = $thread->socialAccount;

        $this->authedClient($account)->put(
            self::BASE_URL."/tweets/{$thread->external_thread_id}/hidden",
            ['hidden' => true],
        );

        $this->logApiUsage($account, 'PUT /2/tweets/:id/hidden', self::COST_WRITE);

        $thread->update([
            'status' => Status::Archived->value,
            'metadata' => array_merge($thread->metadata ?? [], ['hidden' => true]),
        ]);
    }

    private function authedClient(SocialAccount $account): PendingRequest
    {
        return Http::withToken($account->access_token)
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(5);
    }
}
