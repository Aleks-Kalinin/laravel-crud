<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     *
     * The SPA authenticates against the session rather than a bearer token, so
     * nothing secret is returned in the body: the credential is the HttpOnly
     * session cookie Laravel sets on the response, which JavaScript cannot read.
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());

        Auth::login($user);

        $request->session()->regenerate();

        return response()->json($user, 201);
    }

    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->validated(), $request->boolean('remember'))) {
            // 422 rather than 401 so the SPA can tell "these credentials are
            // wrong" apart from "your session expired", which it handles by
            // bouncing the user back to the login screen.
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Issue a fresh session id now that the privilege level changed.
        $request->session()->regenerate();

        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
