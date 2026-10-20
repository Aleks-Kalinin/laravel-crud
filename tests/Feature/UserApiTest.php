<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication', function () {
    $this->getJson('/api/users')->assertUnauthorized();
});

it('does not expose account creation outside registration', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/users', [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertStatus(405);
});

it('lets a user read, update and delete their own account', function () {
    $user = User::factory()->create(['email' => 'keep@example.com']);

    Sanctum::actingAs($user);

    $this->getJson("/api/users/{$user->id}")->assertOk()->assertJsonPath('id', $user->id);

    $this->patchJson("/api/users/{$user->id}", [
        'name' => 'New Name',
        'email' => 'keep@example.com',
    ])->assertOk()->assertJsonPath('name', 'New Name');

    $this->deleteJson("/api/users/{$user->id}")->assertNoContent();

    expect(User::find($user->id))->toBeNull();
});

it('refuses to let one user change another account', function () {
    $victim = User::factory()->create(['password' => 'victim-original-password']);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/users/{$victim->id}")->assertForbidden();

    $this->patchJson("/api/users/{$victim->id}", [
        'password' => 'attacker-chosen-password',
        'password_confirmation' => 'attacker-chosen-password',
    ])->assertForbidden();

    $this->deleteJson("/api/users/{$victim->id}")->assertForbidden();

    $victim->refresh();

    expect(Hash::check('victim-original-password', $victim->password))->toBeTrue()
        ->and(User::find($victim->id))->not->toBeNull();
});

it('rejects an email already taken by someone else', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->patchJson("/api/users/{$user->id}", ['email' => 'taken@example.com'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('hides the password hash from responses', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/users/{$user->id}")->assertOk()->assertJsonMissing(['password']);
});
