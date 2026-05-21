<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ticket_number', 80);
            $table->string('external_offline_number', 100)->nullable();
            $table->string('sale_mode', 20)->default('ONLINE');
            $table->decimal('total_amount', 14, 2);
            $table->decimal('total_possible_prize', 14, 2)->default(0);
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamp('printed_at')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamp('sold_at');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'ticket_number']);
            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['company_id', 'branch_id', 'sold_at']);
            $table->index(['company_id', 'user_id', 'sold_at']);
            $table->index(['company_id', 'status']);
            $table->index('ticket_number');
            $table->index('cash_session_id');
            $table->index('sale_mode');
            $table->index('synced_at');
        });

        Schema::create('ticket_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('lottery_id')->constrained()->restrictOnDelete();
            $table->foreignId('draw_id')->constrained()->restrictOnDelete();
            $table->foreignId('bet_type_id')->constrained()->restrictOnDelete();
            $table->string('number_value', 20);
            $table->string('normalized_number', 20);
            $table->decimal('amount', 14, 2);
            $table->foreignId('payout_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('payout_multiplier', 10, 2);
            $table->decimal('possible_prize', 14, 2);
            $table->foreignId('limit_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('result_position', 30)->nullable();
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamps();

            $table->index('ticket_id');
            $table->index(['company_id', 'branch_id', 'draw_id']);
            $table->index(['company_id', 'draw_id', 'bet_type_id', 'number_value']);
            $table->index(['company_id', 'lottery_id', 'draw_id']);
            $table->index('status');
        });

        Schema::create('print_jobs', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('printer_config_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->text('content');
            $table->string('status', 30)->default('PENDING');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id']);
            $table->index('status');
        });

        Schema::create('printer_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('printer_type', 30)->default('THERMAL');
            $table->string('connection_type', 30)->default('USB');
            $table->string('paper_width', 10)->default('80MM');
            $table->string('printer_identifier');
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamps();

            $table->index(['company_id', 'branch_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_configs');
        Schema::dropIfExists('print_jobs');
        Schema::dropIfExists('ticket_details');
        Schema::dropIfExists('tickets');
    }
};
