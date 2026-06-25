<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ReceivedInvitation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Wipe all data ─────────────────────────────────────────────
        Schema::disableForeignKeyConstraints();
        foreach ([
            'expense_items', 'expense_reports',
            'endorsement_letter_staff', 'endorsement_letters',
            'vehicle_requests', 'travel_order_attachments',
            'approvals', 'audit_logs',
            'travel_order_traveler',
            'travel_request_attachments',
            'invitation_attachments',
            'received_invitation_attachments',
            'invitations', 'received_invitations',
            'travel_orders', 'travel_requests',
            'signatures', 'notifications',
            'itineraries', 'liquidations',
            'users', 'departments',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();

        // ── 2. Departments ───────────────────────────────────────────────
        $this->call(DepartmentSeeder::class);
        $dept = fn(string $abbr) => Department::where('abbreviation', $abbr)->first();

        $pass = Hash::make('password');

        // ── 3. System users ──────────────────────────────────────────────
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@antiquespride.edu.ph',
            'password' => $pass, 'role' => 'admin', 'status' => 'active',
            'requested_position' => 'System Administrator',
            'department_id' => $dept('PRES')?->id,
        ]);

        $president = User::create([
            'name' => 'Dr. Antonio M. Santiago',
            'email' => 'president@antiquespride.edu.ph',
            'password' => $pass, 'role' => 'dean', 'status' => 'active',
            'requested_position' => 'University President',
            'department_id' => $dept('PRES')?->id,
        ]);

        $recordsOfficer = User::create([
            'name' => 'Ricardo B. Torres',
            'email' => 'records@antiquespride.edu.ph',
            'password' => $pass, 'role' => 'records_officer', 'status' => 'active',
            'requested_position' => 'Records Officer',
            'department_id' => $dept('PRES')?->id,
        ]);

        User::create([
            'name' => 'Dr. Maria Cristina V. Aragon',
            'email' => 'vpaa@antiquespride.edu.ph',
            'password' => $pass, 'role' => 'approver', 'status' => 'active',
            'requested_position' => 'Vice President for Academic Affairs',
            'approver_level' => 2, 'approver_type' => 'vp_academic',
            'department_id' => $dept('PRES')?->id,
        ]);

        User::create([
            'name' => 'Dr. Engr. Ramon C. Fontanilla',
            'email' => 'vprei@antiquespride.edu.ph',
            'password' => $pass, 'role' => 'approver', 'status' => 'active',
            'requested_position' => 'Vice President for Research, Extension and Innovation',
            'approver_level' => 2, 'approver_type' => 'vp_research',
            'department_id' => $dept('PRES')?->id,
        ]);

        // ── 4. Deans (one per department) ────────────────────────────────
        $deansData = [
            ['CTE',  'dean.cte@antiquespride.edu.ph',  'Dr. Gemma B. Flores',       'Dean, College of Teacher Education'],
            ['CIT',  'dean.cit@antiquespride.edu.ph',  'Dr. Roberto B. Villanueva', 'Dean, College of Industrial Technology'],
            ['CMS',  'dean.cms@antiquespride.edu.ph',  'Cpt. Leonardo T. Aquino',   'Dean, College of Maritime Studies'],
            ['CCJE', 'dean.ccje@antiquespride.edu.ph', 'Atty. Renato M. Alvarez',   'Dean, College of Criminal Justice Education'],
            ['CAS',  'dean.cas@antiquespride.edu.ph',  'Dr. Josephine C. Bautista', 'Dean, College of Arts and Sciences'],
            ['CEA',  'dean.cea@antiquespride.edu.ph',  'Engr. Joel D. Cabanero',    'Dean, College of Engineering and Architecture'],
            ['CBA',  'dean.cba@antiquespride.edu.ph',  'Dr. Helen A. Magbanua',     'Dean, College of Business and Accountancy'],
            ['CCIS', 'dean.ccis@antiquespride.edu.ph', 'Dr. Oliver R. Dimzon',      'Dean, College of Computer and Information Studies'],
            ['FIN',  'dir.fin@antiquespride.edu.ph',   'Mr. Fernando A. Espinosa',  'Finance Director'],
            ['HR',   'dir.hr@antiquespride.edu.ph',    'Ms. Rosario C. Pascual',    'Director, Human Resources'],
        ];

        foreach ($deansData as [$deptAbbr, $email, $name, $position]) {
            User::create([
                'name' => $name, 'email' => $email,
                'password' => $pass, 'role' => 'dean', 'status' => 'active',
                'requested_position' => $position,
                'department_id' => $dept($deptAbbr)?->id,
            ]);
        }

        // ── 5. Program Chairpersons / Section Heads (level-1 approvers) ──
        $approversData = [
            ['CTE',  'chairperson.cte@antiquespride.edu.ph',  'Dr. Ernesto C. Chua',      'Program Chairperson, Teacher Education'],
            ['CIT',  'chairperson.cit@antiquespride.edu.ph',  'Ma. Luisa T. Reyes',       'Program Chairperson, Information Technology'],
            ['CMS',  'chairperson.cms@antiquespride.edu.ph',  'Cpt. Frederick B. Madrona','Program Chairperson, Maritime Transportation'],
            ['CCJE', 'chairperson.ccje@antiquespride.edu.ph', 'Atty. Carmela T. Velarde', 'Program Chairperson, Criminology'],
            ['CAS',  'chairperson.cas@antiquespride.edu.ph',  'Dr. Narciso B. Perez',     'Program Chairperson, Natural Sciences'],
            ['CEA',  'chairperson.cea@antiquespride.edu.ph',  'Engr. Sylvia C. Manimbo',  'Program Chairperson, Civil Engineering'],
            ['CBA',  'chairperson.cba@antiquespride.edu.ph',  'CPA Eduardo T. Sumbillo',  'Program Chairperson, Accountancy'],
            ['CCIS', 'chairperson.ccis@antiquespride.edu.ph', 'Rhodora B. Bueno',         'Program Chairperson, Computer Science'],
            ['FIN',  'section.fin@antiquespride.edu.ph',      'Maria A. Casquejo',        'Section Head, Accounting'],
            ['HR',   'section.hr@antiquespride.edu.ph',       'Felicitas D. Obrador',     'Section Head, Personnel Management'],
            ['PRES', 'section.pres@antiquespride.edu.ph',     'Estrella M. Velarde',      'Executive Administrative Officer'],
        ];

        foreach ($approversData as [$deptAbbr, $email, $name, $position]) {
            User::create([
                'name' => $name, 'email' => $email,
                'password' => $pass, 'role' => 'approver', 'status' => 'active',
                'requested_position' => $position,
                'approver_level' => 1,
                'department_id' => $dept($deptAbbr)?->id,
            ]);
        }

        // ── 6. Permanent Staff (5 per department, traveler role) ─────────
        $staffData = [
            'CTE' => [
                ['Dr. Maria Luisa B. Pelayo',   'Associate Professor I'],
                ['Eduardo C. Ylagan',            'Instructor II'],
                ['Corazon M. Catubig',           'Instructor I'],
                ['Emmanuel D. Macaraeg',         'Instructor III'],
                ['Nora T. Saguiped',             'Instructor II'],
            ],
            'CIT' => [
                ['Danilo R. Rivera',             'Instructor I'],
                ['Maricel G. Zabala',            'Instructor II'],
                ['Carlos T. Balderama',          'Instructor III'],
                ['Elizabeth S. Ocampo',          'Associate Professor I'],
                ['Pedro D. Cueva',               'Instructor I'],
            ],
            'CMS' => [
                ['Jose C. Dela Cruz',            'Instructor I'],
                ['Ana B. Santos',                'Instructor II'],
                ['Miguel A. Garcia',             'Instructor III'],
                ['Pilar R. Mendoza',             'Instructor I'],
                ['Alfredo M. Torres',            'Associate Professor II'],
            ],
            'CCJE' => [
                ['Gloria D. Reyes',              'Instructor I'],
                ['Ramon C. Villanueva',          'Instructor II'],
                ['Marilou B. Flores',            'Instructor I'],
                ['Eduardo A. Aquino',            'Associate Professor I'],
                ['Lourdes M. Delos Santos',      'Instructor III'],
            ],
            'CAS' => [
                ['Jose C. Bautista',             'Instructor I'],
                ['Maria T. Cabanero',            'Associate Professor II'],
                ['Ricardo D. Espinosa',          'Instructor II'],
                ['Ana M. Pascual',               'Instructor I'],
                ['Emmanuel R. Dimzon',           'Instructor III'],
            ],
            'CEA' => [
                ['Carlos B. Alvarez',            'Instructor I'],
                ['Maria C. Aragon',              'Instructor II'],
                ['Leonardo D. Fontanilla',       'Associate Professor I'],
                ['Gloria T. Santiago',           'Instructor III'],
                ['Ramon M. Macaraeg',            'Instructor II'],
            ],
            'CBA' => [
                ['Maria C. Ylagan',              'Instructor I'],
                ['Eduardo D. Catubig',           'Instructor II'],
                ['Corazon B. Balderama',         'Associate Professor I'],
                ['Emmanuel T. Saguiped',         'Instructor I'],
                ['Nora M. Pelayo',               'Instructor III'],
            ],
            'CCIS' => [
                ['Jose D. Rivera',               'Instructor I'],
                ['Maria R. Zabala',              'Instructor II'],
                ['Carlos M. Ocampo',             'Associate Professor I'],
                ['Elizabeth T. Cueva',           'Instructor I'],
                ['Pedro B. Dela Cruz',           'Instructor II'],
            ],
            'FIN' => [
                ['Maria C. Santos',              'Accountant I'],
                ['Eduardo D. Garcia',            'Accountant II'],
                ['Rosario T. Reyes',             'Administrative Officer II'],
                ['Carlos M. Mendoza',            'Budget Officer'],
                ['Josephine B. Torres',          'Cashier'],
            ],
            'HR' => [
                ['Maria D. Rivera',              'Human Resource Management Officer II'],
                ['Eduardo C. Flores',            'Human Resource Management Officer I'],
                ['Corazon M. Aquino',            'Administrative Aide VI'],
                ['Emmanuel B. Bautista',         'Human Resource Management Officer I'],
                ['Gloria T. Cabanero',           'Administrative Staff'],
            ],
            'PRES' => [
                ['Maria R. Magbanua',            'Administrative Officer V'],
                ['Eduardo D. Espinosa',          'Administrative Aide IV'],
                ['Corazon C. Pascual',           'Administrative Staff III'],
                ['Carlos T. Dimzon',             'Administrative Aide VI'],
                ['Nora M. Alvarez',              'Administrative Officer IV'],
            ],
        ];

        foreach ($staffData as $deptAbbr => $members) {
            $deptId = $dept($deptAbbr)?->id;
            $abbr   = strtolower($deptAbbr);
            foreach ($members as $i => [$name, $position]) {
                User::create([
                    'name' => $name,
                    'email' => "{$abbr}.staff" . ($i + 1) . "@antiquespride.edu.ph",
                    'password' => $pass, 'role' => 'traveler', 'status' => 'active',
                    'requested_position' => $position,
                    'department_id' => $deptId,
                ]);
            }
        }

        // ── 7. Received Invitations (12) ─────────────────────────────────
        $invitationsData = [
            [
                'sender_org'         => 'Department of Science and Technology - Region VI',
                'sender_name'        => 'Dir. Engr. Rowen R. Gelonga',
                'sender_email'       => 'rogelio.gelonga@dost6.gov.ph',
                'sender_phone'       => '(033) 321-0161',
                'event_name'         => 'Regional Science and Technology Week 2026',
                'event_venue'        => 'Iloilo Convention Center, Iloilo City',
                'event_destination'  => 'Iloilo City',
                'event_date_from'    => '2026-07-22',
                'event_date_to'      => '2026-07-24',
                'event_type'         => 'research',
                'description'        => 'Annual Science and Technology Week celebration featuring innovation exhibits, research paper presentations, and awarding ceremonies for outstanding researchers and scientists.',
                'received_at'        => '2026-05-10',
                'status'             => 'new',
            ],
            [
                'sender_org'         => 'Commission on Higher Education',
                'sender_name'        => 'Chairman Prospero E. de Vera III',
                'sender_email'       => 'ched.central@ched.gov.ph',
                'sender_phone'       => '(02) 8441-1177',
                'event_name'         => 'National Higher Education Research Agenda Summit 2026',
                'event_venue'        => 'SMX Convention Center, Mall of Asia Complex, Pasay City',
                'event_destination'  => 'Metro Manila',
                'event_date_from'    => '2026-07-03',
                'event_date_to'      => '2026-07-05',
                'event_type'         => 'academic',
                'description'        => 'Summit to align state and local universities research agenda with the national development goals under Ambisyon Natin 2040. Participation of presidents and research directors is required.',
                'received_at'        => '2026-05-15',
                'status'             => 'new',
            ],
            [
                'sender_org'         => 'Professional Regulation Commission',
                'sender_name'        => 'Chairperson Teresita Manzala',
                'sender_email'       => 'info@prc.gov.ph',
                'sender_phone'       => '(02) 8310-0026',
                'event_name'         => 'CPD Updates Forum for Higher Education Faculty 2026',
                'event_venue'        => 'Grand Dame Hotel, General Luna St., Iloilo City',
                'event_destination'  => 'Iloilo City',
                'event_date_from'    => '2026-06-25',
                'event_date_to'      => '2026-06-25',
                'event_type'         => 'academic',
                'description'        => 'Annual continuing professional development forum for licensed teaching professionals working in higher education institutions in Western Visayas.',
                'received_at'        => '2026-05-18',
                'status'             => 'new',
            ],
            [
                'sender_org'         => 'Technical Education and Skills Development Authority - Region VI',
                'sender_name'        => 'Dir. Ma. Lourdes P. Itindag',
                'sender_email'       => 'region6@tesda.gov.ph',
                'sender_phone'       => '(033) 320-0577',
                'event_name'         => 'Industry 4.0 Readiness Forum for Technical Education',
                'event_venue'        => 'Hotel Del Rio, M.H. del Pilar St., Molo, Iloilo City',
                'event_destination'  => 'Iloilo City',
                'event_date_from'    => '2026-07-08',
                'event_date_to'      => '2026-07-09',
                'event_type'         => 'academic',
                'description'        => 'Forum on integrating Industry 4.0 technologies and competencies into technical-vocational and higher education programs. Covers automation, IoT, and digital transformation.',
                'received_at'        => '2026-05-20',
                'status'             => 'new',
            ],
            [
                'sender_org'         => 'Department of Education - Region VI',
                'sender_name'        => 'Dir. Ramir B. Uytico',
                'sender_email'       => 'deped_r6@deped.gov.ph',
                'sender_phone'       => '(033) 321-3737',
                'event_name'         => 'Basic Education–HEI Curriculum Alignment Workshop',
                'event_venue'        => 'Hardin sa Aklan Hotel, Kalibo, Aklan',
                'event_destination'  => 'Kalibo, Aklan',
                'event_date_from'    => '2026-06-18',
                'event_date_to'      => '2026-06-19',
                'event_type'         => 'academic',
                'description'        => 'Two-day workshop to align the K-12 and Senior High School curriculum frameworks with HEI general education and professional programs in Western Visayas.',
                'received_at'        => '2026-05-12',
                'status'             => 'new',
            ],
            [
                'sender_org'         => 'Philippine Association of State Universities and Colleges',
                'sender_name'        => 'President Tirso A. Ronquillo',
                'sender_email'       => 'secretariat@pasuc.org.ph',
                'sender_phone'       => '(02) 8928-1271',
                'event_name'         => 'PASUC Annual National Convention 2026',
                'event_venue'        => 'Waterfront Cebu City Hotel and Casino, Cebu City',
                'event_destination'  => 'Cebu City',
                'event_date_from'    => '2026-08-05',
                'event_date_to'      => '2026-08-07',
                'event_type'         => 'academic',
                'description'        => 'Annual gathering of presidents and key officials of state universities and colleges for policy discussions, sharing of best practices, and coordination with national government agencies.',
                'received_at'        => '2026-05-05',
                'status'             => 'forwarded',
            ],
            [
                'sender_org'         => 'Iloilo Science and Technology University',
                'sender_name'        => 'President Raul F. Muyong',
                'sender_email'       => 'opc@isat-u.edu.ph',
                'sender_phone'       => '(033) 320-8690',
                'event_name'         => 'Western Visayas Regional Research Symposium 2026',
                'event_venue'        => 'ISAT-U Main Campus, La Paz, Iloilo City',
                'event_destination'  => 'Iloilo City',
                'event_date_from'    => '2026-07-22',
                'event_date_to'      => '2026-07-23',
                'event_type'         => 'research',
                'description'        => 'Inter-university research symposium featuring oral and poster presentations from faculty researchers across Western Visayas. Best paper awards in six categories.',
                'received_at'        => '2026-05-22',
                'status'             => 'new',
            ],
            [
                'sender_org'         => 'University of the Philippines Visayas',
                'sender_name'        => 'Chancellor Clement C. Camposano',
                'sender_email'       => 'chancellor@upv.edu.ph',
                'sender_phone'       => '(033) 315-9221',
                'event_name'         => 'HEI Research Collaboration Forum on Coastal and Marine Sciences',
                'event_venue'        => 'UPV Main Campus, Miagao, Iloilo',
                'event_destination'  => 'Miagao, Iloilo',
                'event_date_from'    => '2026-06-05',
                'event_date_to'      => '2026-06-05',
                'event_type'         => 'research',
                'description'        => 'One-day forum exploring inter-HEI research partnerships in coastal resource management, marine biology, and aquaculture in the Visayas region.',
                'received_at'        => '2026-05-08',
                'status'             => 'forwarded',
            ],
            [
                'sender_org'         => 'Department of Budget and Management - Regional Office VI',
                'sender_name'        => 'Regional Director Mabel A. Mamba',
                'sender_email'       => 'region6@dbm.gov.ph',
                'sender_phone'       => '(033) 337-9250',
                'event_name'         => 'Budget Utilization and Financial Management Seminar for SUCs',
                'event_venue'        => 'Iloilo Grand Hotel, General Luna St., Iloilo City',
                'event_destination'  => 'Iloilo City',
                'event_date_from'    => '2026-07-15',
                'event_date_to'      => '2026-07-15',
                'event_type'         => 'academic',
                'description'        => 'Training seminar on proper budget utilization procedures, COA compliance, and financial reporting requirements for state universities and colleges.',
                'received_at'        => '2026-05-25',
                'status'             => 'new',
            ],
            [
                'sender_org'         => 'ASEAN University Network',
                'sender_name'        => 'Executive Director Chairat Aunnopakul',
                'sender_email'       => 'secretariat@aunsec.org',
                'sender_phone'       => '+66 2 215 0871',
                'event_name'         => 'AUN Quality Assurance Workshop for Philippine HEIs',
                'event_venue'        => 'Pathumwan Princess Hotel, Bangkok, Thailand',
                'event_destination'  => 'Bangkok, Thailand',
                'event_date_from'    => '2026-08-10',
                'event_date_to'      => '2026-08-12',
                'event_type'         => 'academic',
                'description'        => 'Regional workshop on ASEAN quality assurance frameworks and benchmarking for higher education institutions seeking AUN-QA accreditation.',
                'received_at'        => '2026-05-03',
                'status'             => 'declined',
                'declined_reason'    => 'Budget constraints for international travel this fiscal year. The university will prioritize local AUN-QA seminars instead.',
            ],
            [
                'sender_org'         => 'National Research Council of the Philippines',
                'sender_name'        => 'Executive Director Maridon O. Sahagun',
                'sender_email'       => 'nrcp@dost.gov.ph',
                'sender_phone'       => '(02) 8839-0885',
                'event_name'         => 'NRCP Research and Technology Exposition 2026',
                'event_venue'        => 'Philippine International Convention Center, CCP Complex, Manila',
                'event_destination'  => 'Metro Manila',
                'event_date_from'    => '2026-09-03',
                'event_date_to'      => '2026-09-04',
                'event_type'         => 'research',
                'description'        => 'National exposition showcasing research outputs and technology innovations from state universities, research institutes, and DOST agencies. Research paper submissions accepted until July 31.',
                'received_at'        => '2026-05-26',
                'status'             => 'new',
            ],
            [
                'sender_org'         => 'West Visayas State University',
                'sender_name'        => 'President Joselito F. Villaruz',
                'sender_email'       => 'president@wvsu.edu.ph',
                'sender_phone'       => '(033) 320-0870',
                'event_name'         => 'Regional Faculty Development Program: Integrating Technology in Education',
                'event_venue'        => 'WVSU Campus, Luna St., La Paz, Iloilo City',
                'event_destination'  => 'Iloilo City',
                'event_date_from'    => '2026-08-20',
                'event_date_to'      => '2026-08-22',
                'event_type'         => 'academic',
                'description'        => 'Three-day faculty development program on educational technology integration, online pedagogy, and digital learning management systems for HEI faculty in Western Visayas.',
                'received_at'        => '2026-05-27',
                'status'             => 'new',
            ],
        ];

        foreach ($invitationsData as $data) {
            ReceivedInvitation::create(array_merge($data, [
                'received_by' => $president->id,
                'logged_by'   => $recordsOfficer->id,
            ]));
        }
    }
}
