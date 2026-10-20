<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->enforceTokenIdleTimeout();
    }

    /**
     * Expire tokens that have gone unused for too long.
     *
     * Sanctum's own "expiration" config only measures from the moment a token
     * was issued, so an active user is logged out mid-session once they hit it.
     * This adds a second, sliding window measured from last use: active tokens
     * keep working, abandoned ones stop, and "expiration" remains the cap that
     * no token can outlive.
     *
     * Sanctum calls this before it refreshes last_used_at, so the timestamp we
     * read here is genuinely from the previous request.
     */
    private function enforceTokenIdleTimeout(): void
    {
        Sanctum::authenticateAccessTokensUsing(
            function (PersonalAccessToken $accessToken, bool $isValid): bool {
                if (! $isValid) {
                    return false;
                }

                $idleTimeout = config('sanctum.idle_timeout');

                if (! $idleTimeout) {
                    return true;
                }

                // A token that has never been used slides from its creation.
                $lastUsedAt = $accessToken->last_used_at ?? $accessToken->created_at;

                return $lastUsedAt->gt(now()->subMinutes($idleTimeout));
            }
        );
    }
}
