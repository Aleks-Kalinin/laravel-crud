<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

/**
 * These go through the real Sanctum guard with a bearer token, rather than
 * Sanctum::actingAs(), which bypasses token validation entirely.
 */
function tokenFor(User $user): string
{
    return $user->createToken('auth_token')->plainTextToken;
}

function callApiWith(string $token)
{
    auth()->forgetGuards(); // Sanctum caches the resolved user per process.

    return test()->withHeader('Authorization', "Bearer {$token}")->getJson('/api/todos');
}

it('accepts a freshly issued token', function () {
    $token = tokenFor(User::factory()->create());

    callApiWith($token)->assertOk();
});

it('keeps a token alive while it is being used', function () {
    config(['sanctum.idle_timeout' => 24 * 60]);

    $user = User::factory()->create();
    $token = tokenFor($user);

    // Used 23 hours ago - inside the idle window.
    PersonalAccessToken::first()->forceFill(['last_used_at' => now()->subHours(23)])->save();

    callApiWith($token)->assertOk();

    // That request should have slid the window forward.
    expect(PersonalAccessToken::first()->last_used_at->diffInMinutes(now()))->toBeLessThan(1);
});

it('rejects a token left unused past the idle window', function () {
    config(['sanctum.idle_timeout' => 24 * 60]);

    $token = tokenFor(User::factory()->create());

    PersonalAccessToken::first()->forceFill(['last_used_at' => now()->subHours(25)])->save();

    callApiWith($token)->assertUnauthorized();
});

it('slides forward indefinitely for an active user, until the hard cap', function () {
    config(['sanctum.idle_timeout' => 24 * 60, 'sanctum.expiration' => 30 * 24 * 60]);

    $token = tokenFor(User::factory()->create());

    // Simulate daily use across four weeks: each call must keep working.
    $accepted = 0;

    foreach (range(1, 28) as $day) {
        PersonalAccessToken::first()->forceFill([
            'created_at' => now()->subDays(29),
            'last_used_at' => now()->subHours(20),
        ])->save();

        $accepted += callApiWith($token)->status() === 200 ? 1 : 0;
    }

    expect($accepted)->toBe(28);
});

it('enforces the absolute cap even on a token in daily use', function () {
    config(['sanctum.idle_timeout' => 24 * 60, 'sanctum.expiration' => 30 * 24 * 60]);

    $token = tokenFor(User::factory()->create());

    // Actively used an hour ago, but issued 31 days ago.
    PersonalAccessToken::first()->forceFill([
        'created_at' => now()->subDays(31),
        'last_used_at' => now()->subHour(),
    ])->save();

    callApiWith($token)->assertUnauthorized();
});

it('falls back to created_at for a token that has never been used', function () {
    config(['sanctum.idle_timeout' => 24 * 60]);

    $token = tokenFor(User::factory()->create());

    PersonalAccessToken::first()->forceFill([
        'created_at' => now()->subHours(25),
        'last_used_at' => null,
    ])->save();

    callApiWith($token)->assertUnauthorized();
});

it('disables the idle check when idle_timeout is null', function () {
    config(['sanctum.idle_timeout' => null]);

    $token = tokenFor(User::factory()->create());

    PersonalAccessToken::first()->forceFill(['last_used_at' => now()->subYear()])->save();

    callApiWith($token)->assertOk();
});
