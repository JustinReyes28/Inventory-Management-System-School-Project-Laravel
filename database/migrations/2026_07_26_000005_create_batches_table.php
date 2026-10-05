<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('batch_number', 50);
            $table->unsignedInteger('quantity')->default(0);
            $table->date('expiry_date');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['item_id', 'expiry_date'], 'batches_item_expiry_index');
            $table->index('expiry_date', 'batches_expiry_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
