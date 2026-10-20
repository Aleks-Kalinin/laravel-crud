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
        return Todo::with('category')
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->has('completed'), fn ($query) => $query->where('completed', $request->boolean('completed')))
            ->latest()
            ->paginate(10);
    }

    public function show(Todo $todo)
    {
        return response()->json($todo->load('category'), 200);
    }

    public function store(StoreTodoRequest $request)
    {
        $todo = Todo::create($request->validated());

        return response()->json($todo, 201);
    }

    public function update(UpdateTodoRequest $request, Todo $todo)
    {
        $todo->update($request->validated());

        return response()->json($todo, 200);
    }

    public function destroy(Todo $todo)
    {
        $todo->delete();

        return response()->json(null, 204);
    }
}
