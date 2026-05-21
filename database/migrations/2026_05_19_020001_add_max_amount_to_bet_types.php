<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bet_types', function (Blueprint $table): void {
            // Maximum amount allowed per single play of this type (null = no limit)
            $table->decimal('max_amount', 14, 2)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bet_types', function (Blueprint $table): void {
            $table->dropColumn('max_amount');
        });
    }
};
