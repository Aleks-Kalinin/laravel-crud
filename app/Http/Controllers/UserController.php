<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::latest()->paginate(10);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        $this->ensureSelf($request, $user);

        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->ensureSelf($request, $user);

        $user->update($request->validated());

        return response()->json($user, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $this->ensureSelf($request, $user);

        $user->delete();

        return response()->json(null, 204);
    }

    /**
     * There are no roles in this application, so an account is only ever
     * managed by the person who owns it. 403 rather than the 404 used for
     * todos: a user's existence is already public through registration.
     */
    private function ensureSelf(Request $request, User $user): void
    {
        abort_if($user->id !== $request->user()->id, 403);
    }
}
