<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type', 50);
            $table->unsignedInteger('old_quantity')->nullable();
            $table->unsignedInteger('new_quantity')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action_type', 'activity_log_action_index');
            $table->index(['user_id', 'created_at'], 'activity_log_user_created_index');
            $table->index(['item_id', 'created_at'], 'activity_log_item_created_index');
            $table->index('created_at', 'activity_log_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
