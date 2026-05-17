<?php

declare(strict_types=1);

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
        Schema::table('inbox_threads', function (Blueprint $table) {
            $table->foreignUuid('post_platform_id')->nullable()->after('social_account_id')
                ->constrained('post_platforms')->nullOnDelete();

            $table->index(['post_platform_id'], 'inbox_threads_post_platform_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inbox_threads', function (Blueprint $table) {
            $table->dropForeign(['post_platform_id']);
            $table->dropIndex('inbox_threads_post_platform_idx');
            $table->dropColumn('post_platform_id');
        });
    }
};
