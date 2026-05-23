<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('lottery_id')->constrained()->restrictOnDelete();
            $table->foreignId('draw_id')->unique()->constrained()->restrictOnDelete();
            $table->string('first_number', 10);
            $table->string('second_number', 10)->nullable();
            $table->string('third_number', 10)->nullable();
            $table->string('status', 30)->default('DRAFT');
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('registered_at');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('confirmation_notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'lottery_id', 'draw_id']);
            $table->index('status');
        });

        Schema::create('winner_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('ticket_id')->constrained()->restrictOnDelete();
            $table->foreignId('ticket_detail_id')->constrained()->restrictOnDelete();
            $table->foreignId('lottery_id')->constrained()->restrictOnDelete();
            $table->foreignId('draw_id')->constrained()->restrictOnDelete();
            $table->foreignId('bet_type_id')->constrained()->restrictOnDelete();
            $table->string('number_value', 20);
            $table->string('matched_position', 50)->nullable();
            $table->decimal('amount_played', 14, 2);
            $table->decimal('payout_multiplier', 10, 2);
            $table->decimal('prize_amount', 14, 2);
            $table->string('status', 30)->default('PENDING_RELEASE');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'draw_id']);
            $table->index('ticket_id');
            $table->index('ticket_detail_id');
            $table->index('status');
            $table->index('paid_at');
        });

        Schema::create('payment_authorizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('lottery_id')->constrained()->restrictOnDelete();
            $table->foreignId('draw_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('PENDING');
            $table->unsignedInteger('total_winners')->default(0);
            $table->decimal('total_prize_amount', 14, 2)->default(0);
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'draw_id']);
            $table->index('status');
        });

        Schema::create('prize_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('ticket_id')->constrained()->restrictOnDelete();
            $table->foreignId('winner_ticket_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->foreignId('paid_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('paid_at');
            $table->string('status', 30)->default('PAID');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'paid_at']);
            $table->index('ticket_id');
            $table->index('winner_ticket_id');
            $table->index('cash_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prize_payments');
        Schema::dropIfExists('payment_authorizations');
        Schema::dropIfExists('winner_tickets');
        Schema::dropIfExists('results');
    }
};
