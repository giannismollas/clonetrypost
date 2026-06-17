<?php

declare(strict_types=1);

use App\Models\SocialAccount;
use App\Models\Workspace;
use App\Services\Social\Reddit\RedditClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'trypost.platforms.reddit.api' => 'https://oauth.reddit.com',
        'trypost.platforms.reddit.user_agent' => 'web:it.trypost:1.0',
    ]);
    $this->account = SocialAccount::factory()->reddit()->create([
        'workspace_id' => Workspace::factory()->create()->id,
        'access_token' => 'tok',
    ]);
});

test('searchSubreddits returns names from the reddit search endpoint', function () {
    Http::fake([
        'https://oauth.reddit.com/subreddits/search*' => Http::response([
            'data' => ['children' => [
                ['data' => ['display_name' => 'AskReddit', 'title' => 'Ask Reddit', 'subscribers' => 100, 'over18' => false, 'subreddit_type' => 'public']],
                ['data' => ['display_name' => 'pics', 'title' => 'Pics', 'subscribers' => 50, 'over18' => false, 'subreddit_type' => 'public']],
            ]],
        ], 200),
    ]);

    $results = app(RedditClient::class)->searchSubreddits($this->account, 'ask');

    expect($results)->toHaveCount(2)
        ->and($results[0]['name'])->toBe('AskReddit');
});

test('restrictions reports submission type, image allowance and required flair', function () {
    Http::fake([
        'https://oauth.reddit.com/r/AskReddit/about*' => Http::response([
            'data' => ['submission_type' => 'self', 'allow_images' => false],
        ], 200),
        'https://oauth.reddit.com/api/v1/AskReddit/post_requirements*' => Http::response([
            'is_flair_required' => true,
        ], 200),
        'https://oauth.reddit.com/r/AskReddit/api/link_flair_v2*' => Http::response([
            ['id' => 'abc', 'text' => 'Discussion'],
        ], 200),
    ]);

    $restrictions = app(RedditClient::class)->restrictions($this->account, 'AskReddit');

    expect($restrictions['allowed_types'])->toBe(['self'])
        ->and($restrictions['flair_required'])->toBeTrue()
        ->and($restrictions['flairs'][0]['id'])->toBe('abc');
});

test('restrictions adds image (not video or gallery) when images are allowed', function () {
    Http::fake([
        'https://oauth.reddit.com/r/pics/about*' => Http::response(['data' => ['submission_type' => 'any', 'allow_images' => true]], 200),
        'https://oauth.reddit.com/api/v1/pics/post_requirements*' => Http::response(['is_flair_required' => false], 200),
        'https://oauth.reddit.com/r/pics/api/link_flair_v2*' => Http::response([], 200),
    ]);

    $r = app(RedditClient::class)->restrictions($this->account, 'pics');

    expect($r['allowed_types'])->toContain('self')->toContain('link')->toContain('image')
        ->not->toContain('video')->not->toContain('gallery');
});

test('restrictions returns only link type when submission_type is link and images not allowed', function () {
    Http::fake([
        'https://oauth.reddit.com/r/AnnounceOnly/about*' => Http::response([
            'data' => ['submission_type' => 'link', 'allow_images' => false],
        ], 200),
        'https://oauth.reddit.com/api/v1/AnnounceOnly/post_requirements*' => Http::response([
            'is_flair_required' => false,
        ], 200),
        'https://oauth.reddit.com/r/AnnounceOnly/api/link_flair_v2*' => Http::response([], 200),
    ]);

    $r = app(RedditClient::class)->restrictions($this->account, 'AnnounceOnly');

    expect($r['allowed_types'])->toBe(['link'])
        ->not->toContain('self')
        ->not->toContain('image');
});

test('flairs returns empty array when the flair endpoint throws a connection exception', function () {
    Http::fake([
        'https://oauth.reddit.com/r/AskReddit/api/link_flair_v2*' => fn () => throw new ConnectionException('Connection refused'),
    ]);

    $flairs = app(RedditClient::class)->flairs($this->account, 'AskReddit');

    expect($flairs)->toBe([]);
});

test('restrictions returns empty flairs array when the flair endpoint throws a connection exception', function () {
    Http::fake([
        'https://oauth.reddit.com/r/AskReddit/about*' => Http::response([
            'data' => ['submission_type' => 'any', 'allow_images' => false],
        ], 200),
        'https://oauth.reddit.com/api/v1/AskReddit/post_requirements*' => Http::response([
            'is_flair_required' => true,
        ], 200),
        'https://oauth.reddit.com/r/AskReddit/api/link_flair_v2*' => fn () => throw new ConnectionException('Connection refused'),
    ]);

    $r = app(RedditClient::class)->restrictions($this->account, 'AskReddit');

    expect($r['flairs'])->toBe([]);
});

test('info sums nothing for empty fullnames and maps children otherwise', function () {
    Http::fake([
        'https://oauth.reddit.com/api/info*' => Http::response([
            'data' => ['children' => [
                ['data' => ['name' => 't3_abc', 'score' => 12, 'num_comments' => 4, 'url' => 'https://www.reddit.com/r/x/comments/abc/y/']],
            ]],
        ], 200),
    ]);

    expect(app(RedditClient::class)->info($this->account, []))->toBe([]);

    $info = app(RedditClient::class)->info($this->account, ['t3_abc']);
    expect($info['t3_abc']['score'])->toBe(12)
        ->and($info['t3_abc']['num_comments'])->toBe(4);
});
