<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            // Colleges
            ['abbreviation' => 'CTE',  'name' => 'College of Teacher Education'],
            ['abbreviation' => 'CIT',  'name' => 'College of Industrial Technology'],
            ['abbreviation' => 'CMS',  'name' => 'College of Maritime Studies'],
            ['abbreviation' => 'CCJE', 'name' => 'College of Criminal Justice Education'],
            ['abbreviation' => 'CAS',  'name' => 'College of Arts and Sciences'],
            ['abbreviation' => 'CEA',  'name' => 'College of Engineering and Architecture'],
            ['abbreviation' => 'CBA',  'name' => 'College of Business and Accountancy'],
            ['abbreviation' => 'CCIS', 'name' => 'College of Computer and Information Studies'],
            // Administrative offices
            ['abbreviation' => 'FIN', 'name' => 'Finance'],
            ['abbreviation' => 'HR',  'name' => 'HR'],
            // University executive
            ['abbreviation' => 'PRES', 'name' => "President's Office"],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['abbreviation' => $dept['abbreviation']],
                ['name' => $dept['name']]
            );
        }
    }
}
