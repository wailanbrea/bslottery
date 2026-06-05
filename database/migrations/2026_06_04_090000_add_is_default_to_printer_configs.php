<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printer_configs', function (Blueprint $table): void {
            $table->boolean('is_default')->default(false)->after('terminal_name');
            $table->index(['company_id', 'terminal_key', 'is_default'], 'printer_configs_company_terminal_default_idx');
        });
    }

    public function down(): void
    {
        Schema::table('printer_configs', function (Blueprint $table): void {
            $table->dropIndex('printer_configs_company_terminal_default_idx');
            $table->dropColumn('is_default');
        });
    }
};
