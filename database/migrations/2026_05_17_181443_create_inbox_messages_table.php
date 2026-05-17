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
        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('thread_id')->constrained('inbox_threads')->cascadeOnDelete();
            $table->string('external_message_id');
            $table->string('direction');
            $table->string('author_handle')->nullable();
            $table->boolean('author_is_us')->default(false);
            $table->text('body')->nullable();
            $table->json('media')->nullable();
            $table->string('reply_to_external_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->boolean('was_sent_via_trypost')->default(false);
            $table->timestamps();

            $table->unique(['thread_id', 'external_message_id'], 'inbox_messages_external_unique');
            $table->index(['thread_id', 'posted_at'], 'inbox_messages_thread_posted_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
    }
};
