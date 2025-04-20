<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['id' => 1, 'name' => 'Seating'],
            ['id' => 2, 'name' => 'Tables'],
            ['id' => 3, 'name' => 'Storage'],
            ['id' => 4, 'name' => 'Smartphones'],
            ['id' => 5, 'name' => 'Laptops'],
            ['id' => 6, 'name' => 'Wearables'],
            ['id' => 7, 'name' => 'T-Shirts'],
            ['id' => 8, 'name' => 'Footwear'],
            ['id' => 9, 'name' => 'Accessories'],
            ['id' => 10, 'name' => 'Car Parts'],
            ['id' => 11, 'name' => 'Refrigerators'],
            ['id' => 12, 'name' => 'Washing Machines'],
            ['id' => 13, 'name' => 'Necklaces'],
            ['id' => 14, 'name' => 'Fiction Books'],
            ['id' => 15, 'name' => 'Fitness Gear'],
            ['id' => 16, 'name' => 'Action Figures'],
            ['id' => 17, 'name' => 'Skincare'],
            ['id' => 18, 'name' => 'Pet Food'],
            ['id' => 19, 'name' => 'Gardening Tools'],
            ['id' => 20, 'name' => 'Guitars'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}