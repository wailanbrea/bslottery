<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guard de concurrencia para la apertura de caja: un centinela "active_lock" que vale 1 mientras
     * la caja esta viva (OPEN/REOPENED) y NULL cuando se cierra/confirma. El indice unico
     * (branch_id, user_id, active_lock) impide a nivel de BD que un cajero tenga dos cajas activas a
     * la vez, incluso ante dos aperturas simultaneas. NULL se trata como distinto en indices unicos
     * (MySQL y SQLite), por lo que multiples cajas cerradas conviven sin conflicto.
     */
    public function up(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('active_lock')->nullable()->after('status');
        });

        // Backfill: marca como activa UNA sola caja por (branch, user), prefiriendo OPEN y la mas
        // reciente, para no chocar con el indice unico si hubiera datos antiguos con una OPEN y una
        // REOPENED simultaneas.
        $active = DB::table('cash_sessions')
            ->whereIn('status', ['OPEN', 'REOPENED'])
            ->orderByRaw("CASE WHEN status = 'OPEN' THEN 0 ELSE 1 END")
            ->orderByDesc('opened_at')
            ->get(['id', 'branch_id', 'user_id']);

        $seen = [];
        foreach ($active as $row) {
            $key = $row->branch_id.'-'.$row->user_id;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            DB::table('cash_sessions')->where('id', $row->id)->update(['active_lock' => 1]);
        }

        Schema::table('cash_sessions', function (Blueprint $table): void {
            $table->unique(['branch_id', 'user_id', 'active_lock'], 'cash_sessions_active_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table): void {
            $table->dropUnique('cash_sessions_active_unique');
            $table->dropColumn('active_lock');
        });
    }
};
