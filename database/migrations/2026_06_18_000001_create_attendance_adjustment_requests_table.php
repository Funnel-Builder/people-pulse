<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_adjustment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // The attendance record being adjusted (if one exists for that day).
            $table->foreignId('attendance_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['late_entry', 'early_out']);
            $table->date('date');
            $table->time('requested_time'); // actual late clock-in / early clock-out time
            $table->text('reason');
            $table->foreignId('cover_person_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedTinyInteger('current_approval_step')->default(1);
            $table->timestamps();

            // Indexes for common queries (explicit short names to stay within
            // MySQL's 64-character identifier limit).
            $table->index(['user_id', 'status'], 'aar_user_status_idx');
            $table->index(['cover_person_id', 'status'], 'aar_cover_status_idx');
            $table->index(['status', 'current_approval_step'], 'aar_status_step_idx');
            $table->index('date', 'aar_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustment_requests');
    }
};
