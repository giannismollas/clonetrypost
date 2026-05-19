<?php

declare(strict_types=1);

beforeEach(fn () => config()->set('trypost.self_hosted', false));

test('login page loads when google auth is disabled', function () {
    config(['trypost.google_auth_enabled' => false]);

    $response = $this->get(route('login'));

    $response->assertOk();
});

test('login page loads when google auth is enabled', function () {
    config(['trypost.google_auth_enabled' => true]);

    $response = $this->get(route('login'));

    $response->assertOk();
});

test('register page loads when google auth is disabled', function () {
    config(['trypost.google_auth_enabled' => false]);

    $response = $this->get(route('register'));

    $response->assertOk();
});

test('register page loads when google auth is enabled', function () {
    config(['trypost.google_auth_enabled' => true]);

    $response = $this->get(route('register'));

    $response->assertOk();
});

test('login page shares google auth enabled prop as false when disabled', function () {
    config(['trypost.google_auth_enabled' => false]);

    $response = $this->get(route('login'));

    $response->assertOk();

    $page = $response->original->getData()['page'];
    expect($page['props']['googleAuthEnabled'])->toBeFalse();
});

test('login page shares google auth enabled prop as true when enabled', function () {
    config(['trypost.google_auth_enabled' => true]);

    $response = $this->get(route('login'));

    $response->assertOk();

    $page = $response->original->getData()['page'];
    expect($page['props']['googleAuthEnabled'])->toBeTrue();
});

test('google auth redirect route exists', function () {
    $response = $this->get(route('auth.google.redirect'));

    // Should redirect to Google OAuth, not 404
    $response->assertRedirect();
});

test('google auth callback route exists', function () {
    $response = $this->get(route('auth.google.callback'));

    // Should redirect to login on failure (no OAuth code), not 404
    $response->assertRedirect(route('login'));
});

test('register page still shares google auth enabled prop when self_hosted (via pending invite)', function () {
    config()->set('trypost.self_hosted', true);
    config()->set('trypost.google_auth_enabled', true);

    $response = $this
        ->withSession(['pending_invite_id' => 'invite-abc'])
        ->get(route('register'));

    $response->assertOk();
    $page = $response->original->getData()['page'];
    expect($page['props']['googleAuthEnabled'])->toBeTrue();
});
