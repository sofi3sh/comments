<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rolesToCreate = [
            'Admin' => 10,
            'EditorMain' => 20,
            'Staff writer' => 30,
            'Customer' => 50,
            'Blogger' => 40,
            'Company Representative' => 40,
            'Blogger Candidate' => 50,
            'Company Representative Candidate' => 50,
        ];

        foreach ($rolesToCreate as $roleName => $hierarchyLevel) {
            Role::updateOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                ],
                [
                    'rank' => $hierarchyLevel,
                ]
            );
        }
    }

}

