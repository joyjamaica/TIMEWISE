<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'completed_at')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable();
            });
        }

        // Backfill: tasks that are already completed get their last update time
        // as the completion time. This is an estimate, but it gives existing
        // users a starting streak instead of an empty one.
        DB::table('tasks')
            ->where('is_completed', true)
            ->whereNull('completed_at')
            ->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'completed_at')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('completed_at');
            });
        }
    }
};
