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
        Schema::create('attendance_adjustment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_request_id')
                ->constrained('attendance_adjustment_requests', 'id', 'aaa_request_fk')
                ->onDelete('cascade');
            $table->unsignedTinyInteger('step'); // 1=Cover, 2=Manager, 3=Admin
            $table->string('approver_type'); // 'cover_person', 'manager', 'admin'
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            // Unique constraint per step per request (explicit short names to
            // stay within MySQL's 64-character identifier limit).
            $table->unique(['adjustment_request_id', 'step'], 'aaa_request_step_unique');

            // Indexes for finding pending approvals
            $table->index(['approver_id', 'status'], 'aaa_approver_status_idx');
            $table->index(['status', 'step'], 'aaa_status_step_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustment_approvals');
    }
};
