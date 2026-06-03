<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->string('connector_enroll_token', 80)->nullable()->unique()->after('default_printer_id');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->dropUnique(['connector_enroll_token']);
            $table->dropColumn('connector_enroll_token');
        });
    }
};
