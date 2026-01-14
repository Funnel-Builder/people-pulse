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
        Schema::create('user_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 5, 1)->default(0); // Available leave days
            $table->decimal('used', 5, 1)->default(0); // Used leave days
            $table->enum('accrual_type', ['manual', 'attendance'])->default('manual');
            $table->integer('attendance_days_threshold')->nullable(); // Days to earn 1 leave
            $table->date('last_accrual_date')->nullable();
            $table->timestamps();

            // Unique constraint per user and leave type
            $table->unique(['user_id', 'leave_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_leave_balances');
    }
};
