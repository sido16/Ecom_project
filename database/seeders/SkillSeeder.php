<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Skill;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $skills = [
            ['name' => 'UI/UX Design' ], // Design
            ['name' => 'Graphic Design'], // Design
            ['name' => 'Web Development'], // Development
            ['name' => 'Mobile App Development'], // Development
            ['name' => 'SEO'], // Marketing
            ['name' => 'Social Media Marketing'], // Marketing
            ['name' => 'Copywriting'], // Content Creation
            ['name' => 'Video Editing'], // Content Creation
            ['name' => 'Business Strategy'], // Consulting
            ['name' => 'Financial Consulting'], // Consulting
        ];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }
    }
}
?>