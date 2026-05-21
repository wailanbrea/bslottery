<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_funding_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_movement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('source')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('COMPLETED');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['cash_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_funding_transfers');
    }
};
