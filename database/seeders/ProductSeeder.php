<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;


class ProductSeeder extends Seeder
{
    public function run()
    {
        // Products for Supplier 1 (Hadj Furniture Workshop, assuming supplier_id: 1)
        Product::create([
            'supplier_id' => 1,
            'category_id' => 1, // Assumes category id: 1 exists
            'name' => 'Wooden Dining Table',
            'price' => 250.00,
            'description' => 'Handcrafted oak dining table, seats 6',
            'visibility' => 'public',
            'quantity' => 10,
            'minimum_quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Product::create([
            'supplier_id' => 1,
            'category_id' => 1,
            'name' => 'Custom Bookshelf',
            'price' => 150.00,
            'description' => 'Modular wooden bookshelf with adjustable shelves',
            'visibility' => 'public',
            'quantity' => 15,
            'minimum_quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Products for Supplier 2 (Ahmed Metal Works, assuming supplier_id: 2)
        Product::create([
            'supplier_id' => 2,
            'category_id' => 2, // Assumes category id: 2 exists
            'name' => 'Steel Gate',
            'price' => 500.00,
            'description' => 'Durable steel gate with anti-rust coating',
            'visibility' => 'public',
            'quantity' => 5,
            'minimum_quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Product::create([
            'supplier_id' => 2,
            'category_id' => 2,
            'name' => 'Metal Railing',
            'price' => 200.00,
            'description' => 'Custom welded metal railing for stairs',
            'visibility' => 'public',
            'quantity' => 8,
            'minimum_quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}