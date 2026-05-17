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
        Schema::create('inbox_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('kind');
            $table->string('external_thread_id');
            $table->string('participant_handle')->nullable();
            $table->string('participant_avatar')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_user_message_at')->nullable();
            $table->string('status')->default('unread');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['social_account_id', 'platform', 'kind', 'external_thread_id'], 'inbox_threads_external_unique');
            $table->index(['workspace_id', 'status', 'last_message_at'], 'inbox_threads_workspace_status_idx');
            $table->index(['workspace_id', 'platform', 'kind'], 'inbox_threads_workspace_platform_kind_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_threads');
    }
};
