<?php

declare(strict_types=1);

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\Status;
use App\Enums\SocialAccount\Platform;
use App\Models\InboxMessage;
use App\Models\InboxThread;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Inbox\InboxProvider;
use App\Services\Inbox\InboxProviderRegistry;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['user_id' => $this->user->id]);
    $this->user->update(['current_workspace_id' => $this->workspace->id]);
    $this->account = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => Platform::X,
    ]);

    $this->provider = mock(InboxProvider::class);
    $this->provider->shouldReceive('platform')->andReturn(Platform::X);
    app()->instance(InboxProviderRegistry::class, new InboxProviderRegistry([$this->provider]));
});

test('GET /inbox/threads/{thread} returns the thread + messages', function () {
    $thread = InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
    ]);
    InboxMessage::factory()->count(2)->create(['thread_id' => $thread->id]);

    $this->actingAs($this->user)
        ->getJson(route('app.inbox.threads.show', $thread))
        ->assertOk()
        ->assertJsonPath('id', $thread->id)
        ->assertJsonCount(2, 'messages');
});

test('GET /inbox/threads/{thread} blocks access from other workspace', function () {
    $other = Workspace::factory()->create();
    $thread = InboxThread::factory()->create(['workspace_id' => $other->id]);

    $this->actingAs($this->user)
        ->getJson(route('app.inbox.threads.show', $thread))
        ->assertForbidden();
});

test('POST /inbox/threads/{thread}/reply invokes provider->reply for mention thread', function () {
    $thread = InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'kind' => Kind::Mention,
    ]);

    $this->provider->shouldReceive('reply')->once()
        ->with(Mockery::on(fn ($t) => $t->id === $thread->id), 'my reply');

    $this->actingAs($this->user)
        ->postJson(route('app.inbox.threads.reply', $thread), ['body' => 'my reply'])
        ->assertNoContent();
});

test('POST /inbox/threads/{thread}/reply invokes provider->sendDm for dm thread', function () {
    $thread = InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'kind' => Kind::Dm,
    ]);

    $this->provider->shouldReceive('sendDm')->once()
        ->with(Mockery::on(fn ($t) => $t->id === $thread->id), 'hi there');

    $this->actingAs($this->user)
        ->postJson(route('app.inbox.threads.reply', $thread), ['body' => 'hi there'])
        ->assertNoContent();
});

test('POST /inbox/threads/{thread}/hide invokes provider->hideReply', function () {
    $thread = InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'kind' => Kind::Mention,
    ]);

    $this->provider->shouldReceive('hideReply')->once();

    $this->actingAs($this->user)
        ->postJson(route('app.inbox.threads.hide', $thread))
        ->assertNoContent();
});

test('PATCH /inbox/threads/{thread} updates status', function () {
    $thread = InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'status' => Status::Unread,
    ]);

    $this->actingAs($this->user)
        ->patchJson(route('app.inbox.threads.update', $thread), ['status' => 'read'])
        ->assertNoContent();

    expect($thread->fresh()->status)->toBe(Status::Read);
});
