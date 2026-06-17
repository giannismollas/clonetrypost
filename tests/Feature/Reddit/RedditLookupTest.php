<?php

declare(strict_types=1);

use App\Enums\UserWorkspace\Role;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'trypost.platforms.reddit.api' => 'https://oauth.reddit.com',
        'app.user_agent' => 'web:it.trypost:1.0',
    ]);

    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['account_id' => $this->user->account_id, 'user_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => Role::Admin->value]);
    $this->user->update(['current_workspace_id' => $this->workspace->id]);
    $this->user->refresh();

    $this->account = SocialAccount::factory()->reddit()->create(['workspace_id' => $this->workspace->id]);
});

test('searches subreddits for a connected account', function () {
    Http::fake([
        config('trypost.platforms.reddit.api').'/subreddits/search*' => Http::response([
            'data' => ['children' => [['data' => ['display_name' => 'AskReddit', 'subreddit_type' => 'public', 'title' => 'Ask Reddit', 'subscribers' => 1, 'over18' => false]]]],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('app.reddit.subreddits', ['account' => $this->account->id, 'q' => 'ask']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'AskReddit');
});

test('returns empty array when query is blank', function () {
    $this->actingAs($this->user)
        ->getJson(route('app.reddit.subreddits', ['account' => $this->account->id, 'q' => '']))
        ->assertOk()
        ->assertJsonPath('data', []);
});

test('returns restrictions for a subreddit', function () {
    Http::fake([
        config('trypost.platforms.reddit.api').'/r/pics/about*' => Http::response(['data' => ['submission_type' => 'any', 'allow_images' => true]], 200),
        config('trypost.platforms.reddit.api').'/api/v1/pics/post_requirements*' => Http::response(['is_flair_required' => false], 200),
        config('trypost.platforms.reddit.api').'/r/pics/api/link_flair_v2*' => Http::response([], 200),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('app.reddit.restrictions', ['account' => $this->account->id, 'subreddit' => 'pics']))
        ->assertOk()
        ->assertJsonPath('data.allowed_types.0', 'self');
});

test('forbids looking up a reddit account in another workspace', function () {
    $other = SocialAccount::factory()->reddit()->create([
        'workspace_id' => Workspace::factory()->create()->id,
    ]);

    $this->actingAs($this->user)
        ->getJson(route('app.reddit.subreddits', ['account' => $other->id, 'q' => 'x']))
        ->assertForbidden();
});
