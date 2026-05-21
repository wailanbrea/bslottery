<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transfers', function (Blueprint $table): void {
            $table->string('movement_type', 50)->default('SALE')->after('cash_session_id');
            $table->index(['movement_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('bank_transfers', function (Blueprint $table): void {
            $table->dropIndex(['movement_type', 'status']);
            $table->dropColumn('movement_type');
        });
    }
};
