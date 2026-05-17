<?php

declare(strict_types=1);

use App\Enums\Inbox\Status;
use App\Enums\SocialAccount\Platform;
use App\Models\InboxThread;
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

test('inbox filters by platform query param', function () {
    InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $this->account->id,
        'platform' => Platform::X,
    ]);

    $other = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => Platform::Facebook,
    ]);
    InboxThread::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $other->id,
        'platform' => Platform::Facebook,
    ]);

    $this->actingAs($this->user)
        ->get(route('app.inbox.index', ['platform' => 'x']))
        ->assertInertia(fn ($page) => $page->has('threads.data', 1));
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
