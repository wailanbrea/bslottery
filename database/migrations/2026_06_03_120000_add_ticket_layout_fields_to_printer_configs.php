<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printer_configs', function (Blueprint $table): void {
            $table->boolean('show_logo')->default(false)->after('auto_cut');
            $table->boolean('show_qr')->default(true)->after('show_logo');
            $table->boolean('show_phone')->default(true)->after('show_qr');
            $table->boolean('show_address')->default(false)->after('show_phone');
            $table->boolean('show_potential_prize')->default(false)->after('show_address');
            $table->text('footer_text')->nullable()->after('show_potential_prize');
            $table->boolean('open_cash_drawer')->default(false)->after('footer_text');
            $table->unsignedTinyInteger('print_copies')->default(1)->after('open_cash_drawer');
        });
    }

    public function down(): void
    {
        Schema::table('printer_configs', function (Blueprint $table): void {
            $table->dropColumn([
                'show_logo',
                'show_qr',
                'show_phone',
                'show_address',
                'show_potential_prize',
                'footer_text',
                'open_cash_drawer',
                'print_copies',
            ]);
        });
    }
};
