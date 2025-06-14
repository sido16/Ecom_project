<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Workshop;
use App\Models\Merchant;
use App\Models\Importer;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        // Workshop Suppliers
        $supplier1 = Supplier::create([
            'user_id' => 1,
            'business_name' => 'Hadj Furniture Workshop',
            'address' => '456 Algiers St, Algiers, Algeria',
            'description' => 'Custom furniture design and manufacturing',
            'picture' => 'storage/images/logos/figma.png',
            'domain_id' => 1,
        ]);
    
        Workshop::create([
            'supplier_id' => $supplier1->id,
        ]);
    
        $supplier2 = Supplier::create([
            'user_id' => 2,
            'business_name' => 'Ahmed Metal Works',
            'address' => '789 Oran St, Oran, Algeria',
            'description' => 'Precision metal fabrication and welding',
            'picture' => 'storage/images/logos/sketch.png',
            'domain_id' => 2,
        ]);
    
        Workshop::create([
            'supplier_id' => $supplier2->id,
        ]);

        // Merchant Suppliers
        $supplier3 = Supplier::create([
            'user_id' => 3,
            'business_name' => 'Tech Solutions Merchant',
            'address' => '123 Constantine St, Constantine, Algeria',
            'description' => 'Technology and electronics retail',
            'picture' => 'storage/images/logos/google.png',
            'domain_id' => 3,
        ]);

        Merchant::create([
            'supplier_id' => $supplier3->id,
        ]);

        $supplier4 = Supplier::create([
            'user_id' => 4,
            'business_name' => 'Fashion Boutique',
            'address' => '321 Annaba St, Annaba, Algeria',
            'description' => 'Luxury fashion and accessories',
            'picture' => 'storage/images/logos/dribbble.png',
            'domain_id' => 4,
        ]);

        Merchant::create([
            'supplier_id' => $supplier4->id,
        ]);

        // Importer Suppliers
        $supplier5 = Supplier::create([
            'user_id' => 1,
            'business_name' => 'Global Tech Imports',
            'address' => '555 Tlemcen St, Tlemcen, Algeria',
            'description' => 'International technology imports',
            'picture' => 'storage/images/logos/aws.png',
            'domain_id' => 1,
        ]);

        Importer::create([
            'supplier_id' => $supplier5->id,
        ]);

        $supplier6 = Supplier::create([
            'user_id' => 2,
            'business_name' => 'Luxury Goods Importers',
            'address' => '777 Bejaia St, Bejaia, Algeria',
            'description' => 'High-end luxury goods import',
            'picture' => 'storage/images/logos/linkedin.png',
            'domain_id' => 2,
        ]);

        Importer::create([
            'supplier_id' => $supplier6->id,
        ]);

        // Additional Workshop
        $supplier7 = Supplier::create([
            'user_id' => 3,
            'business_name' => 'Creative Design Workshop',
            'address' => '888 Setif St, Setif, Algeria',
            'description' => 'Creative design and prototyping',
            'picture' => 'storage/images/logos/behance.png',
            'domain_id' => 3,
        ]);

        Workshop::create([
            'supplier_id' => $supplier7->id,
        ]);

        // Additional Merchant
        $supplier8 = Supplier::create([
            'user_id' => 4,
            'business_name' => 'Digital Solutions Store',
            'address' => '999 Mostaganem St, Mostaganem, Algeria',
            'description' => 'Digital products and services',
            'picture' => 'storage/images/logos/github.png',
            'domain_id' => 4,
        ]);

        Merchant::create([
            'supplier_id' => $supplier8->id,
        ]);

        // Additional Importer
        $supplier9 = Supplier::create([
            'user_id' => 1,
            'business_name' => 'Agricultural Imports Co',
            'address' => '111 Tiaret St, Tiaret, Algeria',
            'description' => 'Agricultural equipment and supplies',
            'picture' => 'storage/images/logos/bootstrap.png',
            'domain_id' => 1,
        ]);

        Importer::create([
            'supplier_id' => $supplier9->id,
        ]);

        // Mixed Type Supplier
        $supplier10 = Supplier::create([
            'user_id' => 2,
            'business_name' => 'Innovation Hub',
            'address' => '222 Blida St, Blida, Algeria',
            'description' => 'Technology innovation and development',
            'picture' => 'storage/images/logos/react.png',
            'domain_id' => 2,
        ]);

        Workshop::create([
            'supplier_id' => $supplier10->id,
        ]);

       
    }
}
