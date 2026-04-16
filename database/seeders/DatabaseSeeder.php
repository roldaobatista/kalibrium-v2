<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['tecnico', 'gerente', 'administrativo', 'visualizador'] as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName]);
        }

        $this->call(ClienteSeeder::class);
    }
}
