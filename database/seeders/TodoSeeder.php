<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Todo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();

        Todo::factory(10)->recycle($categories)->create();
        Todo::factory(2)->completed()->recycle($categories)->create();
        Todo::factory(2)->recycle($categories)->create(['completed' => false]);
    }
}
