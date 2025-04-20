<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Domain;

class DomainSeeder extends Seeder
{
    public function run()
    {
        $domains = [
            ['id' => 1, 'name' => 'Furniture'],
            ['id' => 2, 'name' => 'Electronics'],
            ['id' => 3, 'name' => 'Clothing'],
            ['id' => 4, 'name' => 'Automotive'],
            ['id' => 5, 'name' => 'Home Appliances'],
            ['id' => 6, 'name' => 'Jewelry'],
            ['id' => 7, 'name' => 'Books'],
            ['id' => 8, 'name' => 'Sports Equipment'],
            ['id' => 9, 'name' => 'Toys'],
            ['id' => 10, 'name' => 'Beauty Products'],
            ['id' => 11, 'name' => 'Food and Beverage'],
            ['id' => 12, 'name' => 'Health and Wellness'],
            ['id' => 13, 'name' => 'Pet Supplies'],
            ['id' => 14, 'name' => 'Office Supplies'],
            ['id' => 15, 'name' => 'Garden and Outdoor'],
            ['id' => 16, 'name' => 'Musical Instruments'],
            ['id' => 17, 'name' => 'Art and Crafts'],
            ['id' => 18, 'name' => 'Travel Gear'],
            ['id' => 19, 'name' => 'Baby Products'],
            ['id' => 20, 'name' => 'Hardware Tools'],
        ];

        foreach ($domains as $domain) {
            Domain::create($domain);
        }
    }
}