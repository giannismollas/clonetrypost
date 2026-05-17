<?php

declare(strict_types=1);

use App\Enums\SocialAccount\Platform;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['user_id' => $this->user->id]);
    $this->user->update(['current_workspace_id' => $this->workspace->id]);
});

test('upgrade redirects to X OAuth with full inbox scopes', function () {
    $account = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => Platform::X,
        'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
    ]);

    $this->actingAs($this->user)
        ->get(route('auth.x.upgrade-scopes', $account))
        ->assertRedirect();
});

test('upgrade refuses if account is not X', function () {
    $account = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => Platform::Facebook,
    ]);

    $this->actingAs($this->user)
        ->get(route('auth.x.upgrade-scopes', $account))
        ->assertNotFound();
});

test('upgrade refuses if account belongs to another workspace', function () {
    $otherWorkspace = Workspace::factory()->create();
    $account = SocialAccount::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'platform' => Platform::X,
    ]);

    $this->actingAs($this->user)
        ->get(route('auth.x.upgrade-scopes', $account))
        ->assertForbidden();
});

test('upgrade requires authentication', function () {
    $account = SocialAccount::factory()->create(['platform' => Platform::X]);

    $this->get(route('auth.x.upgrade-scopes', $account))
        ->assertRedirect(route('login'));
});
