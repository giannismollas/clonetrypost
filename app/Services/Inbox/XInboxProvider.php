<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\MessageDirection;
use App\Enums\Inbox\Status;
use App\Enums\SocialAccount\Platform;
use App\Models\InboxMessage;
use App\Models\InboxSyncState;
use App\Models\InboxThread;
use App\Models\SocialAccount;
use App\Services\Inbox\Concerns\TracksApiUsage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use LogicException;

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
        throw new LogicException('not implemented yet');
    }

    public function reply(InboxThread $thread, string $body): void
    {
        throw new LogicException('not implemented yet');
    }

    public function sendDm(InboxThread $thread, string $body): void
    {
        throw new LogicException('not implemented yet');
    }

    public function hideReply(InboxThread $thread): void
    {
        throw new LogicException('not implemented yet');
    }

    private function authedClient(SocialAccount $account): PendingRequest
    {
        return Http::withToken($account->access_token)
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(5);
    }
}
