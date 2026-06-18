<?php

declare(strict_types=1);

use App\Socialite\RedditProvider;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\User as SocialiteUser;

test('reddit auth url requests a permanent token with the configured scopes', function () {
    config([
        'trypost.platforms.reddit.oauth_api' => 'https://www.reddit.com/api/v1',
        'services.reddit.client_id' => 'cid',
    ]);

    $request = Request::create('/connect/reddit', 'GET');
    $request->setLaravelSession(app('session.store'));

    $provider = new RedditProvider($request, 'cid', 'secret', 'https://trypost.test/accounts/reddit/callback');
    $url = $provider->scopes(['identity', 'submit'])->redirect()->getTargetUrl();

    expect($url)->toContain('https://www.reddit.com/api/v1/authorize')
        ->toContain('duration=permanent')
        ->toContain('client_id=cid')
        ->toContain('scope=identity');
});

function makeProvider(): RedditProvider
{
    config([
        'trypost.platforms.reddit.oauth_api' => 'https://www.reddit.com/api/v1',
        'services.reddit.client_id' => 'cid',
    ]);

    $request = Request::create('/connect/reddit', 'GET');
    $request->setLaravelSession(app('session.store'));

    return new RedditProvider($request, 'cid', 'secret', 'https://trypost.test/accounts/reddit/callback');
}

function callMapUserToObject(RedditProvider $provider, array $data): SocialiteUser
{
    $ref = new ReflectionMethod($provider, 'mapUserToObject');

    return $ref->invoke($provider, $data);
}

test('mapUserToObject strips the query string from icon_img', function () {
    $user = callMapUserToObject(makeProvider(), [
        'id' => 'abc',
        'name' => 'spez',
        'icon_img' => 'https://i.redd.it/x.png?width=256&s=sig',
    ]);

    expect($user)->toBeInstanceOf(SocialiteUser::class)
        ->and($user->getId())->toBe('abc')
        ->and($user->getNickname())->toBe('spez')
        ->and($user->getName())->toBe('spez')
        ->and($user->getAvatar())->toBe('https://i.redd.it/x.png');
});

test('mapUserToObject returns null avatar when icon_img is blank', function () {
    $user = callMapUserToObject(makeProvider(), [
        'id' => 'xyz',
        'name' => 'noavatar',
        'icon_img' => '',
    ]);

    expect($user->getAvatar())->toBeNull();
});
