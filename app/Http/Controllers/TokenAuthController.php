<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;

/**
 * Bearer-token authentication, for clients that cannot hold cookies.
 *
 * The Vue SPA does not use these endpoints: it authenticates against the
 * session via AuthController, which is safer in a browser because the
 * credential is an HttpOnly cookie that JavaScript cannot read. A native
 * mobile app or a third-party integration has no such protection to lose and
 * no cookie jar to rely on, so it trades the token back and forth instead.
 *
 * Both modes are live at the same time. Sanctum's `auth:sanctum` guard checks
 * the session first and falls back to the Authorization header, so every
 * protected route accepts either credential with no change.
 *
 * Tokens issued here expire per config/sanctum.php: an absolute cap from
 * `expiration`, plus the sliding `idle_timeout` window enforced in
 * AppServiceProvider.
 */
class TokenAuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create(Arr::except($request->validated(), ['device_name']));

        return response()->json([
            'access_token' => $this->issueToken($user, $request),
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        // Checked by hand rather than through Auth::attempt(), which would
        // start a session this stateless endpoint has no use for.
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return response()->json([
            'access_token' => $this->issueToken($user, $request),
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Revoke the token that authenticated this request.
     *
     * Reachable with a session too, since it shares the auth:sanctum group. In
     * that case currentAccessToken() returns a TransientToken standing in for
     * the session, which has nothing to delete -- so only a real token is
     * revoked here, and session users should call AuthController@logout.
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();

            return response()->json(['message' => 'Token revoked successfully']);
        }

        return response()->json([
            'message' => 'This request is session-authenticated; use /api/logout instead.',
        ], 400);
    }

    /**
     * Name the token after the calling device when it says so, which is what
     * makes a token list meaningful to a user revoking one of them.
     */
    private function issueToken(User $user, FormRequest $request): string
    {
        $deviceName = $request->validated()['device_name'] ?? 'auth_token';

        return $user->createToken($deviceName)->plainTextToken;
    }
}
