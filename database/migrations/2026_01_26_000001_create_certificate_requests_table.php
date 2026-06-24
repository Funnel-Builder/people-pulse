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
        Schema::create('certificate_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique(); // BDFB/POD/2026/0001
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('purpose'); // visa_application, bank_loan, apartment_leasing, higher_education, other
            $table->string('purpose_other')->nullable(); // Custom purpose when 'other' is selected
            $table->enum('urgency', ['normal', 'urgent'])->default('normal');
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'issued'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_requests');
    }
};
