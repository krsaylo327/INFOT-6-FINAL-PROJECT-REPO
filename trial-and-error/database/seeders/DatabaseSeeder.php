<?php

namespace Database\Seeders;

use App\Models\PartnerOrganization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $universityOrg = PartnerOrganization::firstOrCreate(
            ['name' => 'University of Antique'],
            [
                'address' => 'University of Antique, Antique, Philippines',
                'contact_person' => 'University Administrator',
                'contact_email' => 'admin@university.edu.ph',
            ]
        );

        $deptEducation = PartnerOrganization::firstOrCreate(
            ['name' => 'Department of Education'],
            [
                'address' => 'DepEd Complex, Meralco Avenue, Pasig City',
                'contact_person' => 'Secretary of Education',
                'contact_email' => 'sec@deped.gov.ph',
            ]
        );

        $localGov = PartnerOrganization::firstOrCreate(
            ['name' => 'Local Government Unit of Antique'],
            [
                'address' => 'Capitol Grounds, San Jose, Antique',
                'contact_person' => 'Provincial Governor',
                'contact_email' => 'governor@antique.gov.ph',
            ]
        );

        $ched = PartnerOrganization::firstOrCreate(
            ['name' => 'Commission on Higher Education Region VI'],
            [
                'address' => 'CHED Regional Office VI, Iloilo City',
                'contact_person' => 'Regional Director',
                'contact_email' => 'ched6@ched.gov.ph',
            ]
        );

        PartnerOrganization::firstOrCreate(
            ['name' => 'Private Sector Partner A'],
            [
                'address' => 'Manila, Philippines',
                'contact_person' => 'Company President',
                'contact_email' => 'partner-a@example.com',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | SYSTEM ADMIN
        |--------------------------------------------------------------------------
        */

        User::firstOrCreate(
            ['email' => 'systemadmin@test.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'organization_id' => $universityOrg->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | NON-STAGE COORDINATORS (Senders - can create agreements)
        |--------------------------------------------------------------------------
        */

        User::firstOrCreate(
            ['email' => 'sender@test.com'],
            [
                'name' => 'Sender',
                'password' => Hash::make('password'),
                'role' => 'coordinator',
                'coordinator_stage' => null,
                'organization_id' => $universityOrg->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'college@test.com'],
            [
                'name' => 'Authorized Personnel',
                'password' => Hash::make('password'),
                'role' => 'coordinator',
                'coordinator_stage' => null,
                'organization_id' => $deptEducation->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'unit@test.com'],
            [
                'name' => 'Unit',
                'password' => Hash::make('password'),
                'role' => 'coordinator',
                'coordinator_stage' => null,
                'organization_id' => $localGov->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | COORDINATORS (with stage-scoped workflow permissions)
        |--------------------------------------------------------------------------
        */

        User::firstOrCreate(
            ['email' => 'legal2@test.com'],
            [
                'name' => 'Legal Assistant II',
                'password' => Hash::make('password'),
                'role' => 'coordinator',
                'coordinator_stage' => 'legal_assistant_ii',
                'organization_id' => $universityOrg->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'legal3@test.com'],
            [
                'name' => 'Legal Assistant III',
                'password' => Hash::make('password'),
                'role' => 'coordinator',
                'coordinator_stage' => 'legal_assistant_iii',
                'organization_id' => $universityOrg->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'attorney@test.com'],
            [
                'name' => 'Attorney',
                'password' => Hash::make('password'),
                'role' => 'coordinator',
                'coordinator_stage' => 'attorney',
                'organization_id' => $universityOrg->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'adminaid@test.com'],
            [
                'name' => 'Administrative Aid',
                'password' => Hash::make('password'),
                'role' => 'coordinator',
                'coordinator_stage' => 'administrative_aid',
                'organization_id' => $universityOrg->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'president@test.com'],
            [
                'name' => 'President',
                'password' => Hash::make('password'),
                'role' => 'coordinator',
                'coordinator_stage' => 'president_approval',
                'organization_id' => $universityOrg->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | AUTHORIZED PERSONNEL (partner tracking - no workflow actions)
        |--------------------------------------------------------------------------
        */

        User::firstOrCreate(
            ['email' => 'partner@test.com'],
            [
                'name' => 'Authorized Personnel 3',
                'password' => Hash::make('password'),
                'role' => 'authorized_personnel',
                'organization_id' => $ched->id,
            ]
        );
    }
}
