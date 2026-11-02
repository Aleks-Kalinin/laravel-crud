<?php

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication', function () {
    $this->getJson('/api/todos')->assertUnauthorized();
});

it('lists only the authenticated user\'s todos', function () {
    $user = User::factory()->create();
    $mine = Todo::factory()->count(2)->create(['user_id' => $user->id]);
    Todo::factory()->count(3)->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/todos')->assertOk();

    expect($response->json('total'))->toBe(2)
        ->and(collect($response->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe($mine->pluck('id')->sort()->values()->all());
});

it('assigns the authenticated user when storing a todo', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/todos', ['title' => 'Write the docs'])
        ->assertCreated()
        ->assertJsonPath('user_id', $user->id);
});

it('hides another user\'s todo behind a 404', function () {
    $theirs = Todo::factory()->create();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/todos/{$theirs->id}")->assertNotFound();
    $this->patchJson("/api/todos/{$theirs->id}", ['title' => 'Hijacked'])->assertNotFound();
    $this->deleteJson("/api/todos/{$theirs->id}")->assertNotFound();

    expect($theirs->fresh()->title)->not->toBe('Hijacked');
});

it('lets the owner read, update and delete their todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->getJson("/api/todos/{$todo->id}")->assertOk()->assertJsonPath('id', $todo->id);
    $this->patchJson("/api/todos/{$todo->id}", ['title' => 'Renamed'])->assertOk();
    $this->deleteJson("/api/todos/{$todo->id}")->assertNoContent();

    expect(Todo::find($todo->id))->toBeNull();
});

it('deletes a user\'s todos with the user', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id]);

    $user->delete();

    expect(Todo::find($todo->id))->toBeNull();
});
