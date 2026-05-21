<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bet_types', function (Blueprint $table): void {
            $table->unsignedInteger('numbers_count')->default(1)->after('requires_position');
            $table->boolean('is_cross_lottery')->default(false)->after('numbers_count');
        });
    }

    public function down(): void
    {
        Schema::table('bet_types', function (Blueprint $table): void {
            $table->dropColumn(['numbers_count', 'is_cross_lottery']);
        });
    }
};
