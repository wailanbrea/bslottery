<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production performance indexes for high-volume lottery operations.
 * Designed for 500+ branches with concurrent sales.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tickets — frequent queries: by branch+status and sold_at range
        Schema::table('tickets', function (Blueprint $table): void {
            if (! $this->hasIndex('tickets', 'tickets_company_sold_at_idx')) {
                $table->index(['company_id', 'sold_at'], 'tickets_company_sold_at_idx');
            }
            if (! $this->hasIndex('tickets', 'tickets_branch_status_idx')) {
                $table->index(['branch_id', 'status'], 'tickets_branch_status_idx');
            }
            if (! $this->hasIndex('tickets', 'tickets_user_sold_at_idx')) {
                $table->index(['user_id', 'sold_at'], 'tickets_user_sold_at_idx');
            }
        });

        // Ticket details — number lookups for limit validation and winner calculation
        Schema::table('ticket_details', function (Blueprint $table): void {
            if (! $this->hasIndex('ticket_details', 'td_draw_number_idx')) {
                $table->index(['draw_id', 'number_value'], 'td_draw_number_idx');
            }
            if (! $this->hasIndex('ticket_details', 'td_lottery_draw_idx')) {
                $table->index(['lottery_id', 'draw_id'], 'td_lottery_draw_idx');
            }
        });

        // Limit consumptions — hot path during sales validation
        Schema::table('limit_consumptions', function (Blueprint $table): void {
            if (! $this->hasIndex('limit_consumptions', 'lc_branch_draw_bet_idx')) {
                $table->index(['branch_id', 'draw_id', 'bet_type_id'], 'lc_branch_draw_bet_idx');
            }
        });

        // Cash movements — for caja calculations and reconciliation reports
        Schema::table('cash_movements', function (Blueprint $table): void {
            if (! $this->hasIndex('cash_movements', 'cm_session_type_idx')) {
                $table->index(['cash_session_id', 'type'], 'cm_session_type_idx');
            }
            if (! $this->hasIndex('cash_movements', 'cm_branch_created_idx')) {
                $table->index(['branch_id', 'created_at'], 'cm_branch_created_idx');
            }
        });

        // Cash sessions — supervisor dashboard, current open session lookups
        Schema::table('cash_sessions', function (Blueprint $table): void {
            if (! $this->hasIndex('cash_sessions', 'cs_branch_status_idx')) {
                $table->index(['branch_id', 'status'], 'cs_branch_status_idx');
            }
        });

        // Audit logs — admin audit trail browsing
        Schema::table('audit_logs', function (Blueprint $table): void {
            if (! $this->hasIndex('audit_logs', 'al_company_module_idx')) {
                $table->index(['company_id', 'module', 'created_at'], 'al_company_module_idx');
            }
        });

        // Winner tickets — prize payment queries
        Schema::table('winner_tickets', function (Blueprint $table): void {
            if (! $this->hasIndex('winner_tickets', 'wt_draw_status_idx')) {
                $table->index(['draw_id', 'status'], 'wt_draw_status_idx');
            }
            if (! $this->hasIndex('winner_tickets', 'wt_company_status_idx')) {
                $table->index(['company_id', 'status'], 'wt_company_status_idx');
            }
        });

        // Draws — listing of open draws is hit on every sale page load
        Schema::table('draws', function (Blueprint $table): void {
            if (! $this->hasIndex('draws', 'draws_company_date_status_idx')) {
                $table->index(['company_id', 'draw_date', 'status'], 'draws_company_date_status_idx');
            }
        });

        // Payout rules — resolved on every ticket sale
        Schema::table('payout_rules', function (Blueprint $table): void {
            if (! $this->hasIndex('payout_rules', 'pr_company_bet_lottery_idx')) {
                $table->index(['company_id', 'bet_type_id', 'lottery_id'], 'pr_company_bet_lottery_idx');
            }
        });

        // Offline sync — conflict resolution and batch status checks
        Schema::table('sync_batches', function (Blueprint $table): void {
            if (! $this->hasIndex('sync_batches', 'sb_session_status_idx')) {
                $table->index(['offline_session_id', 'status'], 'sb_session_status_idx');
            }
        });

        // Payroll — period queries by company
        Schema::table('payroll_periods', function (Blueprint $table): void {
            if (! $this->hasIndex('payroll_periods', 'pp_company_start_idx')) {
                $table->index(['company_id', 'period_start'], 'pp_company_start_idx');
            }
        });
    }

    public function down(): void
    {
        $drops = [
            'tickets' => ['tickets_company_sold_at_idx', 'tickets_branch_status_idx', 'tickets_user_sold_at_idx'],
            'ticket_details' => ['td_draw_number_idx', 'td_lottery_draw_idx'],
            'limit_consumptions' => ['lc_branch_draw_bet_idx'],
            'cash_movements' => ['cm_session_type_idx', 'cm_branch_created_idx'],
            'cash_sessions' => ['cs_branch_status_idx'],
            'audit_logs' => ['al_company_module_idx'],
            'winner_tickets' => ['wt_draw_status_idx', 'wt_company_status_idx'],
            'draws' => ['draws_company_date_status_idx'],
            'payout_rules' => ['pr_company_bet_lottery_idx'],
            'sync_batches' => ['sb_session_status_idx'],
            'payroll_periods' => ['pp_company_start_idx'],
        ];

        foreach ($drops as $table => $indexes) {
            Schema::table($table, function (Blueprint $t) use ($indexes): void {
                foreach ($indexes as $index) {
                    try {
                        $t->dropIndex($index);
                    } catch (\Exception $e) {
                        // Index may not exist
                    }
                }
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        // SQLite doesn't support checking indexes the same way — skip duplicate check for dev
        return false;
    }
};
