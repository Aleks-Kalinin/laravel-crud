<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;

class TodoController extends Controller
{
    public function index()
    {
        return Todo::latest()->paginate(10);
    }

    public function show(Todo $todo)
    {
        return response()->json($todo, 200);
    }

    public function store(Request $request)
    {
        $todo = Todo::create($request -> validated());
        
        return response()->json($todo, 201);
    }

    public function update(UpdateTodoRequest $request, Todo $todo)
    {
        $todo->update($request -> validated());

        return response()->json($todo, 200);
    }

    public function destroy(Todo $todo)
    {
        $todo->delete();

        return response()->json(null, 204);
    }
}
