<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            Role::SUPER_ADMIN => 'Super Administrator',
            Role::ACADEMY_ADMIN => 'Academy Administrator',
            Role::TRAINER => 'Trainer',
            Role::STUDENT => 'Student',
        ] as $name => $label) {
            Role::firstOrCreate(['name' => $name], ['label' => $label]);
        }
    }
}
