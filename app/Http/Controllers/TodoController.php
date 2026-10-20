<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->todos()
            ->with('category')
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->has('completed'), fn ($query) => $query->where('completed', $request->boolean('completed')))
            ->latest()
            ->paginate(10);
    }

    public function show(Request $request, Todo $todo)
    {
        $this->ensureOwnedBy($request, $todo);

        return response()->json($todo->load('category'), 200);
    }

    public function store(StoreTodoRequest $request)
    {
        $todo = $request->user()->todos()->create($request->validated());

        return response()->json($todo, 201);
    }

    public function update(UpdateTodoRequest $request, Todo $todo)
    {
        $this->ensureOwnedBy($request, $todo);

        $todo->update($request->validated());

        return response()->json($todo, 200);
    }

    public function destroy(Request $request, Todo $todo)
    {
        $this->ensureOwnedBy($request, $todo);

        $todo->delete();

        return response()->json(null, 204);
    }

    /**
     * Someone else's todo should look like it does not exist at all.
     */
    private function ensureOwnedBy(Request $request, Todo $todo): void
    {
        abort_if($todo->user_id !== $request->user()->id, 404);
    }
}
