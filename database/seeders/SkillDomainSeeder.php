<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SkillDomain;

class SkillDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $domains = [
            ['name' => 'Design'],
            ['name' => 'Development'],
            ['name' => 'Marketing'],
            ['name' => 'Content Creation'],
            ['name' => 'Voice over'],
            ['name' => 'Media buyer'],
            ['name' => 'Confiramtion'],
            
        ];

        foreach ($domains as $domain) {
            SkillDomain::create($domain);
        }
    }
}
?>
