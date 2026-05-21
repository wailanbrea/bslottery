<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('draws', function (Blueprint $table): void {
            $table->time('open_time')->nullable()->after('draw_date');
        });

        DB::table('draws')->whereNull('open_time')->update([
            'open_time' => '00:00',
        ]);
    }

    public function down(): void
    {
        Schema::table('draws', function (Blueprint $table): void {
            $table->dropColumn('open_time');
        });
    }
};
