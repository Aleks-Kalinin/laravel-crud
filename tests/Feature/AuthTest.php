<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

it('registers a user and returns a working token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertCreated()->assertJsonPath('token_type', 'Bearer');

    $user = User::where('email', 'ada@example.com')->firstOrFail();

    // The 'hashed' cast must hash exactly once, or login below would fail.
    expect(Hash::check('secret-password', $user->password))->toBeTrue();

    $this->withHeader('Authorization', 'Bearer '.$response->json('access_token'))
        ->getJson('/api/todos')
        ->assertOk();
});

it('rejects registration with a duplicate email or unconfirmed password', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'different-password',
    ])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
});

it('rejects an over-long email', function () {
    $this->postJson('/api/register', [
        'name' => 'Someone',
        'email' => str_repeat('a', 300).'@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('logs in with correct credentials', function () {
    User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

    $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'secret-password',
    ])->assertOk()->assertJsonStructure(['access_token', 'token_type']);
});

it('rejects a wrong password and an unknown email identically', function () {
    User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

    $wrongPassword = $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'not-the-password',
    ])->assertStatus(401);

    $unknownEmail = $this->postJson('/api/login', [
        'email' => 'ghost@example.com',
        'password' => 'not-the-password',
    ])->assertStatus(401);

    // Identical responses, so neither reveals whether the account exists.
    expect($wrongPassword->json())->toBe($unknownEmail->json());
});

it('revokes only the current token on logout', function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);
    $otherDevice = $user->createToken('other_device')->plainTextToken;

    $token = $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'secret-password',
    ])->json('access_token');

    expect(PersonalAccessToken::count())->toBe(2);

    $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/logout')->assertOk();

    expect(PersonalAccessToken::count())->toBe(1);

    // Clear in-memory guard state so this reads like a genuinely new request.
    auth()->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/todos')->assertUnauthorized();

    auth()->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$otherDevice}")->getJson('/api/todos')->assertOk();
});

it('requires authentication to log out', function () {
    $this->postJson('/api/logout')->assertUnauthorized();
});
