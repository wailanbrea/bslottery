<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotteries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 50);
            $table->string('country', 80)->default('DO');
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index('status');
        });

        Schema::create('draws', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('lottery_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->date('draw_date');
            $table->time('scheduled_time');
            $table->time('close_time');
            $table->timestamp('closed_at')->nullable();
            $table->string('status', 40)->default('OPEN');
            $table->timestamp('last_ticket_at')->nullable();
            $table->timestamp('result_registered_at')->nullable();
            $table->timestamp('result_confirmed_at')->nullable();
            $table->timestamp('winners_calculated_at')->nullable();
            $table->timestamp('payments_released_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'lottery_id', 'draw_date']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'close_time']);
        });

        Schema::create('bet_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->unsignedInteger('digits_count');
            $table->unsignedInteger('min_numbers')->default(1);
            $table->unsignedInteger('max_numbers')->default(1);
            $table->boolean('requires_position')->default(false);
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index('status');
        });

        Schema::create('payout_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('lottery_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('draw_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('bet_type_id')->constrained()->restrictOnDelete();
            $table->string('position', 30)->nullable();
            $table->string('match_type', 30)->default('DIRECT');
            $table->decimal('payout_multiplier', 10, 2);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->boolean('inherit_from_parent')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->string('status', 30)->default('ACTIVE');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id']);
            $table->index(['company_id', 'bet_type_id']);
            $table->index(['company_id', 'lottery_id', 'draw_id']);
            $table->index(['effective_from', 'effective_to']);
            $table->index('status');
        });

        Schema::create('limit_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('lottery_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('draw_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('bet_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('rule_type', 30);
            $table->string('number_value', 10)->nullable();
            $table->string('number_from', 10)->nullable();
            $table->string('number_to', 10)->nullable();
            $table->json('numbers_json')->nullable();
            $table->decimal('max_amount_per_number', 14, 2);
            $table->decimal('max_total_amount', 14, 2)->nullable();
            $table->string('policy', 30)->default('BLOCK_FULL');
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->string('status', 30)->default('ACTIVE');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id']);
            $table->index(['company_id', 'lottery_id', 'draw_id']);
            $table->index(['company_id', 'bet_type_id']);
            $table->index('rule_type');
            $table->index('status');
            $table->index(['effective_from', 'effective_to']);
        });

        Schema::create('limit_consumptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('lottery_id')->constrained()->restrictOnDelete();
            $table->foreignId('draw_id')->constrained()->restrictOnDelete();
            $table->foreignId('bet_type_id')->constrained()->restrictOnDelete();
            $table->string('number_value', 10);
            $table->decimal('sold_amount', 14, 2)->default(0);
            $table->decimal('reserved_offline_amount', 14, 2)->default(0);
            $table->decimal('cancelled_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'lottery_id', 'draw_id', 'bet_type_id', 'number_value'], 'limit_consumptions_unique');
            $table->index(['company_id', 'draw_id']);
            $table->index(['company_id', 'branch_id', 'draw_id']);
            $table->index('number_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('limit_consumptions');
        Schema::dropIfExists('limit_rules');
        Schema::dropIfExists('payout_rules');
        Schema::dropIfExists('bet_types');
        Schema::dropIfExists('draws');
        Schema::dropIfExists('lotteries');
    }
};
