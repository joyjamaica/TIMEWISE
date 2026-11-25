
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semester_class_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('semester_start');
            $table->date('semester_end');

            // Stores recurring class information.
            $table->json('classes');

            $table->timestamps();

            // One schedule per user per semester.
            $table->unique(
                ['user_id', 'semester_start'],
                'semester_class_user_semester_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_class_schedules');
    }
};
