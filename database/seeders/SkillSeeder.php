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
            ['name' => 'UI/UX Design', 'domain_id' => 1], // Design
            ['name' => 'Graphic Design', 'domain_id' => 1], // Design
            ['name' => 'Web Development', 'domain_id' => 2], // Development
            ['name' => 'Mobile App Development', 'domain_id' => 2], // Development
            ['name' => 'SEO', 'domain_id' => 3], // Marketing
            ['name' => 'Social Media Marketing', 'domain_id' => 3], // Marketing
            ['name' => 'Copywriting', 'domain_id' => 4], // Content Creation
            ['name' => 'Video Editing', 'domain_id' => 4], // Content Creation
            ['name' => 'Business Strategy', 'domain_id' => 5], // Consulting
            ['name' => 'Financial Consulting', 'domain_id' => 5], // Consulting
        ];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }
    }
}
?>