<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use Database\Seeders\CommuneSeeder;
use Database\Seeders\WilayaSeeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            DomainSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            SkillDomainSeeder::class,
            SkillSeeder::class,
            StudioServiceSeeder::class,
            WilayaSeeder::class,
            CommuneSeeder::class,
            ProductSeeder::class,
            ServiceProviderSeeder::class,
        ]);
    }
}
