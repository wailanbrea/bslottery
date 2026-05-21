<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_monitoring_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('alert_enabled')->default(true);
            $table->decimal('loss_threshold', 14, 2)->default(0);
            $table->decimal('minimum_expected_cash', 14, 2)->nullable();
            $table->decimal('top_play_alert_amount', 14, 2)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id'], 'branch_monitoring_company_branch_unique');
            $table->index(['company_id', 'alert_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_monitoring_settings');
    }
};
