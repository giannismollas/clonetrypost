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
        Schema::create('inbox_sync_state', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('kind');
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_cursor')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['social_account_id', 'platform', 'kind'], 'inbox_sync_state_account_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_sync_state');
    }
};
