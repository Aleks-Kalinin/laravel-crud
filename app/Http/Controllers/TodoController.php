<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Todo;

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
        $validateData = $request -> validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean'
        ]);

        $todo = Todo::create($validateData);
        
        return response()->json($todo, 201);
    }

    public function update(Request $request, Todo $todo)
    {
        $validateData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean'
        ]);

        $todo->update($validateData);

        return response()->json($todo, 200);
    }

    public function destroy(Todo $todo)
    {
        $todo->delete();

        return response()->json(null, 204);
    }
}
