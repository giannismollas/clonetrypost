<?php

declare(strict_types=1);

use App\Enums\Inbox\Status;
use App\Enums\SocialAccount\Platform;
use App\Models\InboxThread;
use App\Models\Post;
use App\Models\PostPlatform;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['user_id' => $this->user->id]);
    $this->user->update(['current_workspace_id' => $this->workspace->id]);
    $this->account = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => Platform::X,
    ]);
});

test('GET /inbox renders the Inertia page with workspace threads', function () {
    InboxThread::factory()->count(3)->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.inbox.index'))
        ->assertInertia(fn ($page) => $page
            ->component('inbox/Index')
            ->has('threads.data', 3));
});

test('inbox excludes threads from other workspaces', function () {
    $other = Workspace::factory()->create();
    InboxThread::factory()->create(['workspace_id' => $other->id]);
    InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.inbox.index'))
        ->assertInertia(fn ($page) => $page->has('threads.data', 1));
});

test('inbox defaults to the first account when no account query param is set', function () {
    InboxThread::factory()->count(2)->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'platform' => Platform::X,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.inbox.index'))
        ->assertInertia(fn ($page) => $page
            ->where('selected_account_id', $this->account->id)
            ->has('threads.data', 2));
});

test('inbox scopes threads to the account query param', function () {
    InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'platform' => Platform::X,
    ]);

    $other = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => Platform::X,
    ]);
    InboxThread::factory()->count(2)->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $other->id,
        'platform' => Platform::X,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.inbox.index', ['account' => $other->id]))
        ->assertInertia(fn ($page) => $page
            ->where('selected_account_id', $other->id)
            ->has('threads.data', 2));
});

test('inbox falls back to the first account when account query param is invalid', function () {
    InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'platform' => Platform::X,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.inbox.index', ['account' => 'non-existent-id']))
        ->assertInertia(fn ($page) => $page
            ->where('selected_account_id', $this->account->id)
            ->has('threads.data', 1));
});

test('inbox filters by status', function () {
    InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'status' => Status::Unread,
    ]);
    InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'status' => Status::Archived,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.inbox.index', ['status' => 'unread']))
        ->assertInertia(fn ($page) => $page->has('threads.data', 1));
});

test('inbox exposes X accounts that still need scope upgrade via accounts prop', function () {
    $this->account->update([
        'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access', 'dm.read', 'dm.write', 'tweet.moderate.write'],
    ]);

    $needsUpgrade = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => Platform::X,
        'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
    ]);
    SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => Platform::X,
        'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access', 'dm.read', 'dm.write', 'tweet.moderate.write'],
    ]);

    $this->actingAs($this->user)
        ->get(route('app.inbox.index'))
        ->assertInertia(fn ($page) => $page
            ->has('accounts', 3)
            ->where('accounts', fn ($accounts) => collect($accounts)
                ->where('id', $needsUpgrade->id)
                ->first()['requires_inbox_scope_upgrade'] === true
            ));
});

test('inbox scopes threads to a specific post when ?post= is set', function () {
    $post = Post::factory()->create(['workspace_id' => $this->workspace->id]);
    $platform = PostPlatform::factory()->create([
        'post_id' => $post->id,
        'social_account_id' => $this->account->id,
    ]);

    InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'post_platform_id' => $platform->id,
    ]);
    InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'post_platform_id' => null,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.inbox.index', ['post' => $post->id]))
        ->assertInertia(fn ($page) => $page->has('threads.data', 1));
});

test('inbox accounts prop includes per-account unread counts', function () {
    InboxThread::factory()->count(2)->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'status' => Status::Unread,
    ]);
    InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'status' => Status::Read,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.inbox.index'))
        ->assertInertia(fn ($page) => $page
            ->where('accounts', fn ($accounts) => collect($accounts)
                ->where('id', $this->account->id)
                ->first()['unread_count'] === 2
            ));
});
