<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_timetables', function (Blueprint $table) {
            $table->id();

            // Core relations
            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('class_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained()
                ->cascadeOnDelete();

            // Day & timing
            $table->enum('day_of_week', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ]);

            $table->unsignedTinyInteger('period_number'); // 1,2,3,4...

            $table->time('start_time');
            $table->time('end_time');

            // Optional
            $table->string('room_number')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Prevent duplicate periods
            $table->unique([
                'class_id',
                'day_of_week',
                'period_number'
            ], 'unique_class_day_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_timetables');
    }
};
