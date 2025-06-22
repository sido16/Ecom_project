<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServiceProvider;

class ServiceProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceProviders = [
            [
                'user_id' => 1,
                'description' => 'Experienced web developer specializing in e-commerce solutions',
                'skill_domain_id' => 1,
                'starting_price' => 5000,
                'skill_ids' => [1, 2, 3],
            ],
            [
                'user_id' => 2,
                'description' => 'Professional graphic designer with a focus on branding',
                'skill_domain_id' => 2,
                'starting_price' => 3000,
                'skill_ids' => [4, 5],
            ],
            [
                'user_id' => 3,
                'description' => null,
                'skill_domain_id' => 3,
                'starting_price' => null,
                'skill_ids' => [6],
            ],
            [
                'user_id' => 4,
                'description' => 'Mobile app developer proficient in cross-platform frameworks',
                'skill_domain_id' => 4,
                'starting_price' => 7500,
                'skill_ids' => [7, 8, 9],
            ],
        ];

        foreach ($serviceProviders as $provider) {
            // Create or update the service provider
            $serviceProvider = ServiceProvider::create([
                'user_id' => $provider['user_id'],
                'description' => $provider['description'],
                'skill_domain_id' => $provider['skill_domain_id'],
                'starting_price' => $provider['starting_price'],
            ]);

            // Attach skills to the service provider (many-to-many relationship)
            $serviceProvider->skills()->attach($provider['skill_ids']);
        }
    }
}
