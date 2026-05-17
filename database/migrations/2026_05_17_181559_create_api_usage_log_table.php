<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_usage_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('social_account_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('endpoint');
            $table->decimal('cost_usd', 12, 6)->default(0);
            $table->timestamp('occurred_at');

            $table->index(['workspace_id', 'occurred_at'], 'api_usage_log_workspace_occurred_idx');
            $table->index(['platform', 'occurred_at'], 'api_usage_log_platform_occurred_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_usage_log');
    }
};
