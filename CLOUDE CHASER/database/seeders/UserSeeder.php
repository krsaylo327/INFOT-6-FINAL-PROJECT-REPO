<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $pres = Department::where('abbreviation', 'PRES')->first();
        $cit  = Department::where('abbreviation', 'CIT')->first();

        // ── Admin ────────────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@antiquespride.edu.ph'],
            [
                'name'               => 'System Admin',
                'password'           => Hash::make('password'),
                'role'               => 'admin',
                'status'             => 'active',
                'requested_position' => 'System Administrator',
                'department_id'      => $pres?->id,
            ]
        );

        // ── President's Office (university-wide dean) ────────────────────
        User::updateOrCreate(
            ['email' => 'president@antiquespride.edu.ph'],
            [
                'name'               => 'Dr. Antonio M. Santiago',
                'password'           => Hash::make('password'),
                'role'               => 'dean',
                'status'             => 'active',
                'requested_position' => 'University President',
                'department_id'      => $pres?->id,
            ]
        );

        // ── Dean (CIT) ───────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'dean.cit@antiquespride.edu.ph'],
            [
                'name'               => 'Dr. Roberto B. Villanueva',
                'password'           => Hash::make('password'),
                'role'               => 'dean',
                'status'             => 'active',
                'requested_position' => 'Dean, College of Industrial Technology',
                'department_id'      => $cit?->id,
            ]
        );

        // ── Approver (Level 1 — CIT immediate supervisor) ────────────────
        User::updateOrCreate(
            ['email' => 'approver@antiquespride.edu.ph'],
            [
                'name'               => 'Ma. Luisa Reyes',
                'password'           => Hash::make('password'),
                'role'               => 'approver',
                'status'             => 'active',
                'requested_position' => 'Program Chairperson',
                'approver_level'     => 1,
                'department_id'      => $cit?->id,
            ]
        );

        // ── VP for Academic Affairs (VPAA) ───────────────────────────────
        User::updateOrCreate(
            ['email' => 'vpaa@antiquespride.edu.ph'],
            [
                'name'               => 'Dr. Maria Cristina V. Aragon',
                'password'           => Hash::make('password'),
                'role'               => 'approver',
                'status'             => 'active',
                'requested_position' => 'Vice President for Academic Affairs',
                'approver_level'     => 2,
                'approver_type'      => 'vp_academic',
                'department_id'      => $pres?->id,
            ]
        );

        // ── VP for Research, Extension and Innovation (VPREI) ────────────
        User::updateOrCreate(
            ['email' => 'vprei@antiquespride.edu.ph'],
            [
                'name'               => 'Dr. Engr. Ramon C. Fontanilla',
                'password'           => Hash::make('password'),
                'role'               => 'approver',
                'status'             => 'active',
                'requested_position' => 'Vice President for Research, Extension and Innovation',
                'approver_level'     => 2,
                'approver_type'      => 'vp_research',
                'department_id'      => $pres?->id,
            ]
        );

        // ── Travelers (CIT faculty) ───────────────────────────────────────
        $travelers = [
            [
                'email'    => 'traveler@antiquespride.edu.ph',
                'name'     => 'Juan Dela Cruz',
                'position' => 'Instructor I',
            ],
            [
                'email'    => 'maria.santos@antiquespride.edu.ph',
                'name'     => 'Maria Santos',
                'position' => 'Instructor II',
            ],
        ];

        foreach ($travelers as $t) {
            User::updateOrCreate(
                ['email' => $t['email']],
                [
                    'name'               => $t['name'],
                    'password'           => Hash::make('password'),
                    'role'               => 'traveler',
                    'status'             => 'active',
                    'requested_position' => $t['position'],
                    'department_id'      => $cit?->id,
                ]
            );
        }
    }
}
