<?php

declare(strict_types=1);

use App\Enums\PostPlatform\ContentType;
use App\Enums\SocialAccount\Platform;
use App\Exceptions\Social\RedditPublishException;
use App\Models\Post;
use App\Models\PostPlatform;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Social\Reddit\RedditPublisher;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'trypost.platforms.reddit.api' => 'https://oauth.reddit.com',
        'trypost.platforms.reddit.user_agent' => 'web:it.trypost:1.0',
    ]);

    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['user_id' => $this->user->id]);
    $this->account = SocialAccount::factory()->reddit()->create([
        'workspace_id' => $this->workspace->id,
        'access_token' => 'tok',
    ]);
    $this->post = Post::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'content' => 'Hello Reddit',
    ]);
});

function redditPlatform(array $subreddits): PostPlatform
{
    return PostPlatform::factory()->create([
        'post_id' => test()->post->id,
        'social_account_id' => test()->account->id,
        'platform' => Platform::Reddit,
        'content_type' => ContentType::RedditPost,
        'enabled' => true,
        'meta' => ['subreddits' => $subreddits],
    ]);
}

test('publishes a self post and resolves the url via info', function () {
    Http::fake([
        'https://oauth.reddit.com/api/submit*' => Http::response(['json' => ['errors' => [], 'data' => ['name' => 't3_abc', 'id' => 'abc']]], 200),
        'https://oauth.reddit.com/api/info*' => Http::response(['data' => ['children' => [
            ['data' => ['name' => 't3_abc', 'url' => 'https://www.reddit.com/r/test/comments/abc/x/']],
        ]]], 200),
    ]);

    $platform = redditPlatform([['name' => 'test', 'title' => 'My title', 'type' => 'self']]);
    $result = app(RedditPublisher::class)->publish($platform);

    expect($result['id'])->toContain('abc')->and($result['url'])->toContain('reddit.com');
    Http::assertSent(fn ($r) => str_contains($r->url(), '/api/submit') && $r['kind'] === 'self' && $r['sr'] === 'test' && $r['title'] === 'My title');
});

test('submits a link post with the url', function () {
    Http::fake([
        'https://oauth.reddit.com/api/submit*' => Http::response(['json' => ['errors' => [], 'data' => ['name' => 't3_l', 'id' => 'l']]], 200),
        'https://oauth.reddit.com/api/info*' => Http::response(['data' => ['children' => []]], 200),
    ]);

    $platform = redditPlatform([['name' => 'test', 'title' => 'T', 'type' => 'link', 'url' => 'https://example.com']]);
    app(RedditPublisher::class)->publish($platform);

    Http::assertSent(fn ($r) => str_contains($r->url(), '/api/submit') && $r['kind'] === 'link' && $r['url'] === 'https://example.com');
});

test('submits to multiple subreddits and aggregates ids', function () {
    Http::fake([
        'https://oauth.reddit.com/api/submit*' => Http::sequence()
            ->push(['json' => ['errors' => [], 'data' => ['name' => 't3_one', 'id' => 'one']]])
            ->push(['json' => ['errors' => [], 'data' => ['name' => 't3_two', 'id' => 'two']]]),
        'https://oauth.reddit.com/api/info*' => Http::response(['data' => ['children' => []]], 200),
    ]);

    $platform = redditPlatform([
        ['name' => 'a', 'title' => 'T', 'type' => 'self'],
        ['name' => 'b', 'title' => 'T', 'type' => 'self'],
    ]);
    $result = app(RedditPublisher::class)->publish($platform);

    expect($result['id'])->toContain('one')->toContain('two');
});

test('records partial failure when a later subreddit fails', function () {
    Http::fake([
        'https://oauth.reddit.com/api/submit*' => Http::sequence()
            ->push(['json' => ['errors' => [], 'data' => ['name' => 't3_one', 'id' => 'one']]])
            ->push(['json' => ['errors' => [['SUBREDDIT_NOEXIST', 'that subreddit does not exist', 'sr']]]], 200),
        'https://oauth.reddit.com/api/info*' => Http::response(['data' => ['children' => []]], 200),
    ]);

    $platform = redditPlatform([
        ['name' => 'ok', 'title' => 'T', 'type' => 'self'],
        ['name' => 'bad', 'title' => 'T', 'type' => 'self'],
    ]);

    expect(fn () => app(RedditPublisher::class)->publish($platform))->toThrow(RedditPublishException::class);
    expect(data_get($platform->fresh()->meta, 'results.0.id'))->toContain('one');
});

test('throws when no subreddit is configured', function () {
    $platform = redditPlatform([]);
    expect(fn () => app(RedditPublisher::class)->publish($platform))->toThrow(RedditPublishException::class);
});

test('sends nsfw, spoiler and flair fields in the payload', function () {
    Http::fake([
        'https://oauth.reddit.com/api/submit*' => Http::response(['json' => ['errors' => [], 'data' => ['name' => 't3_abc', 'id' => 'abc']]], 200),
        'https://oauth.reddit.com/api/info*' => Http::response(['data' => ['children' => []]], 200),
    ]);

    $platform = redditPlatform([[
        'name' => 'test',
        'title' => 'T',
        'type' => 'self',
        'nsfw' => true,
        'spoiler' => true,
        'flair_id' => 'flair-123',
        'flair_text' => 'Discussion',
    ]]);
    app(RedditPublisher::class)->publish($platform);

    Http::assertSent(fn ($r) => str_contains($r->url(), '/api/submit')
        && $r['nsfw'] === 'true'
        && $r['spoiler'] === 'true'
        && $r['flair_id'] === 'flair-123'
        && $r['flair_text'] === 'Discussion');
});

test('throws when a subreddit has no title', function () {
    $platform = redditPlatform([['name' => 'test', 'type' => 'self']]);
    expect(fn () => app(RedditPublisher::class)->publish($platform))->toThrow(RedditPublishException::class);
});

test('throws for video and gallery since they are not yet supported', function () {
    foreach (['video', 'gallery'] as $type) {
        $platform = redditPlatform([['name' => 'test', 'title' => 'T', 'type' => $type]]);
        expect(fn () => app(RedditPublisher::class)->publish($platform))->toThrow(RedditPublishException::class);
    }
});

test('throws when an image post has no media attached', function () {
    $platform = redditPlatform([['name' => 'pics', 'title' => 'My photo', 'type' => 'image']]);

    expect(fn () => app(RedditPublisher::class)->publish($platform))
        ->toThrow(RedditPublishException::class);
});

test('throws when s3 upload returns a non-2xx status', function () {
    test()->post->update([
        'media' => [[
            'id' => 'm2',
            'path' => 'media/2026-01/photo.jpg',
            'url' => 'https://cdn.test/photo.jpg',
            'mime_type' => 'image/jpeg',
            'original_filename' => 'photo.jpg',
        ]],
    ]);

    Http::fake([
        'https://oauth.reddit.com/api/media/asset*' => Http::response([
            'args' => [
                'action' => '//reddit-uploads.s3.amazonaws.com',
                'fields' => [['name' => 'key', 'value' => 'abc/photo.jpg']],
            ],
            'asset' => ['asset_id' => 'a1'],
        ], 200),
        'https://cdn.test/photo.jpg' => Http::response('binarybytes', 200),
        'https://reddit-uploads.s3.amazonaws.com' => Http::response('Forbidden', 403),
    ]);

    $platform = redditPlatform([['name' => 'pics', 'title' => 'My photo', 'type' => 'image']]);

    expect(fn () => app(RedditPublisher::class)->publish($platform))
        ->toThrow(RedditPublishException::class, 'Failed to upload the image to Reddit.');
});

test('uploads an image then submits the asset url', function () {
    test()->post->update([
        'media' => [[
            'id' => 'm1',
            'path' => 'media/2026-01/photo.jpg',
            'url' => 'https://cdn.test/photo.jpg',
            'mime_type' => 'image/jpeg',
            'original_filename' => 'photo.jpg',
        ]],
    ]);

    Http::fake([
        'https://oauth.reddit.com/api/media/asset*' => Http::response([
            'args' => [
                'action' => '//reddit-uploads.s3.amazonaws.com',
                'fields' => [['name' => 'key', 'value' => 'abc/photo.jpg'], ['name' => 'policy', 'value' => 'p']],
            ],
            'asset' => ['asset_id' => 'a1'],
        ], 200),
        'https://reddit-uploads.s3.amazonaws.com' => Http::response('<?xml version="1.0"?><PostResponse><Location>https://reddit-uploads.s3.amazonaws.com/abc/photo.jpg</Location></PostResponse>', 201),
        'https://cdn.test/photo.jpg' => Http::response('binarybytes', 200),
        'https://oauth.reddit.com/api/submit*' => Http::response(['json' => ['errors' => [], 'data' => ['name' => 't3_img', 'id' => 'img']]], 200),
        'https://oauth.reddit.com/api/info*' => Http::response(['data' => ['children' => []]], 200),
    ]);

    $platform = redditPlatform([['name' => 'pics', 'title' => 'My photo', 'type' => 'image']]);
    $result = app(RedditPublisher::class)->publish($platform);

    expect($result['id'])->toContain('img');
    Http::assertSent(fn ($r) => str_contains($r->url(), '/api/submit') && isset($r['url']) && str_contains((string) $r['url'], 'photo.jpg'));
});
