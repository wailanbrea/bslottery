<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('limit_rules', function (Blueprint $table): void {
            // PER_BRANCH: each branch has its own independent counter
            // COMPANY:    single counter shared across all branches of the company
            $table->string('scope', 20)->default('PER_BRANCH')->after('rule_type');
        });
    }

    public function down(): void
    {
        Schema::table('limit_rules', function (Blueprint $table): void {
            $table->dropColumn('scope');
        });
    }
};
