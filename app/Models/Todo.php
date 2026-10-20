<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Table('todos')]
#[Fillable (['title', 'description', 'completed'])]
class Todo extends Model
{
    use HasFactory;
    
    protected function casts(): array
    {
        protected $attributes = [
            'completed' => false,
        ]

        return [
            'completed' => 'boolean',
        ];
    }
}
