<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_limit_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offline_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lottery_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('draw_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bet_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number_value', 20)->nullable();
            $table->decimal('allocated_amount', 14, 2);
            $table->decimal('used_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['offline_session_id']);
            $table->index(['draw_id', 'bet_type_id', 'number_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_limit_allocations');
    }
};
