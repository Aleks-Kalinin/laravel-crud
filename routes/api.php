<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TokenAuthController;

/*
|--------------------------------------------------------------------------
| Session authentication -- used by the Vue SPA
|--------------------------------------------------------------------------
|
| These set an HttpOnly session cookie. Requests from SANCTUM_STATEFUL_DOMAINS
| must carry the X-XSRF-TOKEN header, which the SPA's api() client adds.
|
*/

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

/*
|--------------------------------------------------------------------------
| Bearer-token authentication -- for mobile and third-party clients
|--------------------------------------------------------------------------
|
| Same credentials, but the caller gets an access_token to put in an
| Authorization header rather than a cookie. Not used by the SPA. Requests
| without an Origin from a stateful domain skip session and CSRF middleware
| entirely, so a native client needs no CSRF token.
|
*/

Route::prefix('token')->middleware('throttle:6,1')->group(function () {
    Route::post('/register', [TokenAuthController::class, 'register']);
    Route::post('/login', [TokenAuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Protected routes -- reachable with either credential
|--------------------------------------------------------------------------
|
| auth:sanctum resolves the session first and falls back to the bearer token,
| so nothing below needs to know how the caller authenticated.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('todos', TodoController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('users', UserController::class)->except('store');

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/token/logout', [TokenAuthController::class, 'logout']);
});
