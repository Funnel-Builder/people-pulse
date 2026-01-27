<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL requires raw SQL to modify ENUM
        DB::statement("ALTER TABLE certificate_requests MODIFY COLUMN status ENUM('pending', 'authorized', 'approved', 'rejected', 'issued', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE certificate_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'issued') DEFAULT 'pending'");
    }
};
