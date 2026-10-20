<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
        });

        // Existing todos predate ownership, so hand them to the first user.
        $fallbackUserId = DB::table('users')->min('id');

        if ($fallbackUserId !== null) {
            DB::table('todos')->whereNull('user_id')->update(['user_id' => $fallbackUserId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
