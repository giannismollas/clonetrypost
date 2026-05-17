<?php

declare(strict_types=1);

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\Status;
use App\Enums\SocialAccount\Platform;
use App\Models\InboxSyncState;
use App\Models\InboxThread;
use App\Models\SocialAccount;
use App\Models\Workspace;

test('InboxThread casts platform, kind, status to enums', function () {
    $workspace = Workspace::factory()->create();
    $account = SocialAccount::factory()->create(['workspace_id' => $workspace->id]);

    $thread = InboxThread::factory()->create([
        'workspace_id' => $workspace->id,
        'social_account_id' => $account->id,
        'platform' => Platform::X->value,
        'kind' => Kind::Mention->value,
        'status' => Status::Unread->value,
    ]);

    expect($thread->platform)->toBe(Platform::X);
    expect($thread->kind)->toBe(Kind::Mention);
    expect($thread->status)->toBe(Status::Unread);
    expect($thread->metadata)->toBeArray();
});

test('InboxThread belongs to workspace and social account', function () {
    $thread = InboxThread::factory()->create();

    expect($thread->workspace)->toBeInstanceOf(Workspace::class);
    expect($thread->socialAccount)->toBeInstanceOf(SocialAccount::class);
});

test('Workspace::inboxThreads returns threads for the workspace', function () {
    $workspace = Workspace::factory()->create();
    InboxThread::factory()->count(2)->create(['workspace_id' => $workspace->id]);
    InboxThread::factory()->create(); // other workspace, must not appear

    expect($workspace->inboxThreads()->count())->toBe(2);
});

test('SocialAccount::inboxThreads returns threads for the account', function () {
    $account = SocialAccount::factory()->create();
    InboxThread::factory()->count(3)->create(['social_account_id' => $account->id]);

    expect($account->inboxThreads()->count())->toBe(3);
});

test('SocialAccount::inboxSyncStates returns sync state rows', function () {
    $account = SocialAccount::factory()->create();
    InboxSyncState::factory()->create(['social_account_id' => $account->id]);

    expect($account->inboxSyncStates()->count())->toBe(1);
});
