<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        // Akun Admin
        $adminId = DB::table('users')->insertGetId([
            'name'       => 'Makki Husnan',
            'email'      => 'admin@gudangsafe.com',
            'password'   => Hash::make('GudangSafe@2025'),
            'role'       => 'admin',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Akun Pegawai Contoh
        DB::table('users')->insert([
            'name'       => 'Penjaga Gudang',
            'email'      => 'pegawai@gudangsafe.com',
            'password'   => Hash::make('Pegawai@2025'),
            'role'       => 'employee',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Default Threshold
        // Nilai aman untuk penyimpanan pupuk & pestisida
        // Sumber: standar penyimpanan agrokimia Indonesia
        DB::table('thresholds')->insert([
            'temp_min'     => 15.00,
            'temp_max'     => 30.00,
            'humidity_min' => 40.00,
            'humidity_max' => 70.00,
            'updated_by'   => $adminId,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->command->info('Seeder berhasil! Data awal sudah siap.');
        $this->command->info('Admin  : admin@gudangsafe.com  | GudangSafe@2025');
        $this->command->info('Pegawai: pegawai@gudangsafe.com | Pegawai@2025');
        $this->command->warn('Ganti password setelah deploy ke production!');
    }
}
