<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

/**
 * Disposable demo inventory used to demonstrate CRUD, role differences, and
 * reports. Local/testing only and only when the catalog is empty, so it is
 * safe to run repeatedly without duplicating data.
 */
class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        if (Item::query()->withDeleted()->exists()) {
            return;
        }

        $categories = [
            'Electronics' => [
                ['sku' => 'ELEC-1001', 'name' => 'Wireless Mouse', 'price' => 25.50, 'quantity' => 42, 'low_stock_threshold' => 10],
                ['sku' => 'ELEC-1002', 'name' => 'USB-C Dock', 'price' => 89.99, 'quantity' => 4, 'low_stock_threshold' => 5],
                ['sku' => 'ELEC-1003', 'name' => '27-inch Monitor', 'price' => 219.00, 'quantity' => 12, 'low_stock_threshold' => 4],
            ],
            'Stationery' => [
                ['sku' => 'STAT-2001', 'name' => 'Notebook A5', 'price' => 4.25, 'quantity' => 130, 'low_stock_threshold' => 30],
                ['sku' => 'STAT-2002', 'name' => 'Gel Pen Black', 'price' => 1.10, 'quantity' => 8, 'low_stock_threshold' => 20],
            ],
            'Groceries' => [
                ['sku' => 'GROC-3001', 'name' => 'Ground Coffee 500g', 'price' => 9.75, 'quantity' => 26, 'low_stock_threshold' => 8],
            ],
        ];

        foreach ($categories as $categoryName => $items) {
            $category = Category::query()->firstOrCreate(['category_name' => $categoryName]);

            foreach ($items as $attributes) {
                $item = Item::query()->firstOrCreate(
                    ['sku' => $attributes['sku']],
                    [
                        'name' => $attributes['name'],
                        'category_id' => $category->getKey(),
                        'price' => $attributes['price'],
                        'quantity' => $attributes['quantity'],
                        'low_stock_threshold' => $attributes['low_stock_threshold'],
                    ],
                );

                if ($item->batches()->doesntExist()) {
                    Batch::query()->create([
                        'item_id' => $item->getKey(),
                        'batch_number' => substr(str_replace('-', '', $attributes['sku']).'-A', 0, 50),
                        'quantity' => min($attributes['quantity'], 10),
                        'expiry_date' => now()->addDays(rand(5, 45))->toDateString(),
                    ]);
                }
            }
        }
    }
}
