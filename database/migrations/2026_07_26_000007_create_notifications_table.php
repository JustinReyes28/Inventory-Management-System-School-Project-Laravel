<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title', 150);
            $table->text('message');
            $table->string('link', 100)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('type', 'notifications_type_index');
            $table->index(['user_id', 'is_read', 'created_at'], 'notifications_user_read_created_index');
            $table->index(['user_id', 'created_at'], 'notifications_user_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
