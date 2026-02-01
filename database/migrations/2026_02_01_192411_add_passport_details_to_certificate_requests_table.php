<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->string('passport_number')->nullable()->after('end_date');
            $table->date('passport_issue_date')->nullable()->after('passport_number');
            $table->date('passport_expiry_date')->nullable()->after('passport_issue_date');
            $table->string('passport_issue_place')->nullable()->after('passport_expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropColumn(['passport_number', 'passport_issue_date', 'passport_expiry_date', 'passport_issue_place']);
        });
    }
};
