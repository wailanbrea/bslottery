<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printer_configs', function (Blueprint $table): void {
            $table->string('terminal_key', 100)->nullable()->after('device_id');
            $table->string('terminal_name', 150)->nullable()->after('terminal_key');
            $table->string('printing_mode', 30)->default('RAW_ESCPOS')->after('paper_width');
            $table->boolean('auto_cut')->default(true)->after('printing_mode');
            $table->timestamp('last_test_at')->nullable()->after('status');

            $table->index(['company_id', 'terminal_key'], 'printer_configs_company_terminal_idx');
        });
    }

    public function down(): void
    {
        Schema::table('printer_configs', function (Blueprint $table): void {
            $table->dropIndex('printer_configs_company_terminal_idx');
            $table->dropColumn([
                'terminal_key',
                'terminal_name',
                'printing_mode',
                'auto_cut',
                'last_test_at',
            ]);
        });
    }
};
