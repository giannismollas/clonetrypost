<?php

declare(strict_types=1);

use App\Enums\SocialAccount\Platform;
use App\Enums\SocialAccount\Status;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

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

test('callback updates existing account scopes when upgrade flow is in session', function () {
    $account = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => Platform::X,
        'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
        'platform_user_id' => 'twitter-uid-123',
        'access_token' => 'old-token',
        'refresh_token' => 'old-refresh',
    ]);

    session([
        'social_connect_workspace' => $this->workspace->id,
        'x_scope_upgrade_account_id' => $account->id,
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->token = 'new-token';
    $socialiteUser->refreshToken = 'new-refresh';
    $socialiteUser->expiresIn = 7200;
    $socialiteUser->approvedScopes = [
        'tweet.read',
        'tweet.write',
        'users.read',
        'media.write',
        'offline.access',
        'dm.read',
        'dm.write',
        'tweet.moderate.write',
    ];

    Socialite::shouldReceive('driver')
        ->with('x')
        ->andReturn(Mockery::mock([
            'user' => $socialiteUser,
        ]));

    $response = $this->actingAs($this->user)->get(route('app.social.x.callback'));

    $response->assertOk();
    $response->assertViewIs('auth.social-callback');
    $response->assertViewHas('success', true);

    $account->refresh();

    expect($account->access_token)->toBe('new-token');
    expect($account->refresh_token)->toBe('new-refresh');
    expect($account->scopes)->toContain('dm.read', 'dm.write', 'tweet.moderate.write');
    expect($account->status)->toBe(Status::Connected);

    // Ensure no new account was created.
    expect(SocialAccount::query()->where('platform', Platform::X)->count())->toBe(1);

    // Upgrade session key should be cleared after use.
    expect(session('x_scope_upgrade_account_id'))->toBeNull();
});
