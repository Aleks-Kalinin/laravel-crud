<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Todo extends Model
{
    use HasFactory;

    protected $attributes = [
        'completed' => false,
    ];

    protected $fillable = [
        'title',
        'description',
        'completed',
        'category_id',
        'user_id',
    ];
    
    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
