<?php

declare(strict_types=1);

use App\Enums\UserWorkspace\Role;
use App\Models\Post;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create([
        'account_id' => $this->owner->account_id,
        'user_id' => $this->owner->id,
    ]);
    $this->owner->update(['current_workspace_id' => $this->workspace->id]);

    $this->viewer = User::factory()->create([
        'account_id' => $this->owner->account_id,
        'current_workspace_id' => $this->workspace->id,
    ]);
    $this->workspace->members()->attach($this->viewer->id, ['role' => Role::Viewer->value]);

    $this->member = User::factory()->create([
        'account_id' => $this->owner->account_id,
        'current_workspace_id' => $this->workspace->id,
    ]);
    $this->workspace->members()->attach($this->member->id, ['role' => Role::Member->value]);

    $this->post = Post::factory()->create(['workspace_id' => $this->workspace->id]);
});

test('a viewer cannot delete a post', function () {
    $this->actingAs($this->viewer)
        ->delete(route('app.posts.destroy', $this->post))
        ->assertForbidden();

    $this->assertDatabaseHas('posts', ['id' => $this->post->id]);
});

test('a viewer cannot create an automation', function () {
    $this->actingAs($this->viewer)
        ->post(route('app.automations.store'))
        ->assertForbidden();
});

test('a member can create an automation', function () {
    $this->actingAs($this->member)
        ->post(route('app.automations.store'))
        ->assertRedirect();
});

test('a viewer can comment on a post', function () {
    $this->actingAs($this->viewer)
        ->postJson(route('app.posts.comments.store', $this->post), ['body' => 'Looks good!'])
        ->assertSuccessful();

    $this->assertDatabaseHas('post_comments', [
        'post_id' => $this->post->id,
        'user_id' => $this->viewer->id,
    ]);
});

test('a viewer opening a draft post sees the read-only page instead of the editor', function () {
    $this->actingAs($this->viewer)
        ->get(route('app.posts.show', $this->post))
        ->assertOk();
});

test('a member opening a draft post is redirected to the editor', function () {
    $this->actingAs($this->member)
        ->get(route('app.posts.show', $this->post))
        ->assertRedirect(route('app.posts.edit', $this->post));
});

test('a viewer cannot open the post editor directly', function () {
    $this->actingAs($this->viewer)
        ->get(route('app.posts.edit', $this->post))
        ->assertForbidden();
});
