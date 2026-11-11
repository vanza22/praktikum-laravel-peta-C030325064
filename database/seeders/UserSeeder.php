<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Enums\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $sekolahs = Sekolah::orderBy('id')->take(2)->get();

        $akun = [
            ['Admin Sistem', 'admin@sig.test', Role::ADMIN->value, null],
            ['Dinas Pendidikan', 'dinas@sig.test', Role::DINAS->value, null],
            ['Operator Sekolah 1', 'sekolah1@sig.test',
                Role::SEKOLAH->value, $sekolahs->get(0)?->id],
            ['Operator Sekolah 2', 'sekolah2@sig.test',
                Role::SEKOLAH->value, $sekolahs->get(1)?->id],
        ];

        foreach ($akun as [$nama, $email, $role, $sekolahId]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nama,
                    'password' => 'password123',
                    'role' => $role,
                    'sekolah_id' => $sekolahId,
                ]
            );
        }
    }
}