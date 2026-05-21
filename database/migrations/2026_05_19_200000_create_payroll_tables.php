<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employee advances (cash given before payday)
        Schema::create('employee_advances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('status', 30)->default('PENDING'); // PENDING, APPROVED, PAID, REJECTED
            $table->text('notes')->nullable();
            $table->date('requested_at');
            $table->date('paid_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'status']);
        });

        // Employee loans with installment schedule
        Schema::create('employee_loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('principal', 14, 2);
            $table->decimal('balance', 14, 2);
            $table->decimal('installment', 14, 2)->comment('Amount deducted each payroll period');
            $table->string('status', 30)->default('ACTIVE'); // ACTIVE, PAID_OFF, CANCELLED
            $table->text('notes')->nullable();
            $table->date('started_at');
            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'status']);
        });

        // Payroll periods (one run per period per company)
        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('frequency', 20)->default('MONTHLY'); // WEEKLY, BIWEEKLY, MONTHLY
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 30)->default('DRAFT'); // DRAFT, APPROVED, PAID
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'period_start', 'period_end']);
            $table->index(['company_id', 'status']);
        });

        // One row per employee per payroll period
        Schema::create('payroll_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            // Gross components
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->decimal('commission', 14, 2)->default(0);
            $table->decimal('bonus', 14, 2)->default(0);
            $table->decimal('gross_pay', 14, 2)->default(0);
            // Deductions
            $table->decimal('advance_deduction', 14, 2)->default(0);
            $table->decimal('loan_deduction', 14, 2)->default(0);
            $table->decimal('cash_shortage', 14, 2)->default(0)->comment('Confirmed caja faltante deducted');
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            // Net
            $table->decimal('net_pay', 14, 2)->default(0);
            $table->string('status', 30)->default('PENDING'); // PENDING, PAID
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
            $table->index(['company_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('employee_advances');
    }
};
