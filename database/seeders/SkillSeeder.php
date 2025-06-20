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
    ['name' => 'UI/UX Design'], // Design
    ['name' => 'Graphic Design'], // Design
    ['name' => 'Motion Graphics'], // Design
    ['name' => '3D Modeling'], // Design
    ['name' => 'Web Development'], // Development
    ['name' => 'Mobile App Development'], // Development
    ['name' => 'Backend Development'], // Development
    ['name' => 'DevOps'], // Development
    ['name' => 'Social Media Marketing'], // Marketing
    ['name' => 'SEO Optimization'], // Marketing
    ['name' => 'Email Marketing'], // Marketing
    ['name' => 'Copywriting'], // Content Creation
    ['name' => 'Video Editing'], // Content Creation
    ['name' => 'Scriptwriting'], // Content Creation
    ['name' => 'Blog Writing'], // Content Creation
    ['name' => 'Voice Acting'], // Voice over
    ['name' => 'Audio Editing'], // Voice over
    ['name' => 'Narration'], // Voice over
    ['name' => 'Media Planning'], // Media buyer
    ['name' => 'Ad Campaign Management'], // Media buyer
    ['name' => 'Programmatic Advertising'], // Media buyer
    ['name' => 'Customer Service Communication'], // Confirmation
    ['name' => 'Order Verification'], // Confirmation
    
];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }
    }
}
?>
