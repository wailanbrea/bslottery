<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_movements', function (Blueprint $table): void {
            $table->string('payment_method', 30)->default('CASH')->after('direction');
            $table->unsignedBigInteger('bank_transfer_id')->nullable()->after('payment_method');

            $table->index(['payment_method', 'created_at']);
            $table->index('bank_transfer_id');
        });

        Schema::create('bank_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('bank_name', 120);
            $table->string('reference', 120);
            $table->decimal('amount', 14, 2);
            $table->string('direction', 10);
            $table->string('status', 30)->default('PENDING');
            $table->timestamp('transferred_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evidence_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'reference']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['cash_session_id', 'status']);
        });

        Schema::create('cash_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_session_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('closed_by')->constrained('users')->restrictOnDelete();
            $table->decimal('opening_amount', 14, 2);
            $table->decimal('cash_sales_total', 14, 2)->default(0);
            $table->decimal('transfer_sales_total', 14, 2)->default(0);
            $table->decimal('cash_prizes_total', 14, 2)->default(0);
            $table->decimal('transfer_prizes_total', 14, 2)->default(0);
            $table->decimal('cash_in_total', 14, 2)->default(0);
            $table->decimal('cash_out_total', 14, 2)->default(0);
            $table->decimal('expenses_total', 14, 2)->default(0);
            $table->decimal('cancellations_total', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2);
            $table->decimal('counted_cash', 14, 2);
            $table->decimal('difference_amount', 14, 2)->default(0);
            $table->decimal('shortage_amount', 14, 2)->default(0);
            $table->decimal('surplus_amount', 14, 2)->default(0);
            $table->string('status', 30)->default('PENDING_REVIEW');
            $table->text('notes')->nullable();
            $table->timestamp('closed_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'closed_at']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('cash_count_denominations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_reconciliation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('denomination', 8, 2);
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['cash_reconciliation_id', 'type', 'denomination']);
            $table->index(['company_id', 'branch_id']);
        });

        Schema::create('cash_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cash_reconciliation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50);
            $table->string('severity', 30)->default('WARNING');
            $table->string('status', 30)->default('OPEN');
            $table->string('title', 180);
            $table->text('description');
            $table->decimal('amount', 14, 2)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['type', 'severity']);
            $table->index(['cash_session_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_incidents');
        Schema::dropIfExists('cash_count_denominations');
        Schema::dropIfExists('cash_reconciliations');
        Schema::dropIfExists('bank_transfers');

        Schema::table('cash_movements', function (Blueprint $table): void {
            $table->dropIndex(['payment_method', 'created_at']);
            $table->dropIndex(['bank_transfer_id']);
            $table->dropColumn(['payment_method', 'bank_transfer_id']);
        });
    }
};
