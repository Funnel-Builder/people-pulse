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
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('step'); // 1=Cover, 2=Manager, 3=Admin
            $table->string('approver_type'); // 'cover_person', 'manager', 'admin'
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            // Unique constraint per step per leave
            $table->unique(['leave_id', 'step']);

            // Index for finding pending approvals
            $table->index(['approver_id', 'status']);
            $table->index(['status', 'step']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
    }
};
