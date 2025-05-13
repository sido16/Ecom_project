<?php

namespace Database\Seeders;

use App\Models\StudioService;
use Illuminate\Database\Seeder;

class StudioServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['service' => 'Lighting Equipment'],
            ['service' => 'Camera Rental'],
            ['service' => 'Soundproofing'],
            ['service' => 'Green Screen'],
            ['service' => 'Editing Software'],
        ];

        foreach ($services as $service) {
            StudioService::create($service);
        }
    }
}