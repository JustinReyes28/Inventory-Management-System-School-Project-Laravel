<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(10);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->index(['category_id', 'is_deleted'], 'items_category_active_index');
            $table->index(['is_deleted', 'quantity'], 'items_active_quantity_index');
            $table->index(['is_deleted', 'low_stock_threshold'], 'items_active_threshold_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
