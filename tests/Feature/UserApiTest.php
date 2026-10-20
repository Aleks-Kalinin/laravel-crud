<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication', function () {
    $this->getJson('/api/users')->assertUnauthorized();
});

it('creates a user with a hashed password and hides it', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/users', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertCreated()->assertJsonMissing(['password']);

    $user = User::find($response->json('id'));

    expect($user->email)->toBe('ada@example.com')
        ->and($user->password)->not->toBe('secret-password')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue();
});

it('rejects a duplicate email and an unconfirmed password', function () {
    Sanctum::actingAs(User::factory()->create());
    $existing = User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/users', [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'different-password',
    ])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
});

it('lets a user keep their own email on update', function () {
    $user = User::factory()->create(['email' => 'keep@example.com']);

    Sanctum::actingAs($user);

    $this->patchJson("/api/users/{$user->id}", [
        'name' => 'New Name',
        'email' => 'keep@example.com',
    ])->assertOk()->assertJsonPath('name', 'New Name');
});

it('deletes a user', function () {
    Sanctum::actingAs(User::factory()->create());
    $target = User::factory()->create();

    $this->deleteJson("/api/users/{$target->id}")->assertNoContent();

    expect(User::find($target->id))->toBeNull();
});
