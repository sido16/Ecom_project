<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Workshop;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        $supplier1 = Supplier::create([
            'user_id' => 1, // Assumes user with email: hadj@gmail.com has id: 1
            'business_name' => 'Hadj Furniture Workshop',
            'address' => '456 Algiers St, Algiers, Algeria',
            'description' => 'Custom furniture design and manufacturing',
            'picture' => 'pictures/hadj_workshop.jpg',
            'domain_id' => 1, // Assumes domain id: 1 is Furniture
        ]);
    
        Workshop::create([
            'supplier_id' => $supplier1->id,
        ]);
    
        $supplier2 = Supplier::create([
            'user_id' => 2, // Assumes user with email: ahmed@gmail.com has id: 2
            'business_name' => 'Ahmed Metal Works',
            'address' => '789 Oran St, Oran, Algeria',
            'description' => 'Precision metal fabrication and welding',
            'picture' => 'pictures/ahmed_metal.jpg',
            'domain_id' => 2, // Assumes domain id: 2 is Metalwork
        ]);
    
        Workshop::create([
            'supplier_id' => $supplier2->id,
        ]);
    }
}
