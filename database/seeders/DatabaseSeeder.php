<?php

namespace Database\Seeders;

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
        // Credentials remain interactive-only; vertical-slice seeds contain no user or secret.
        $this->call(Vs001Seeder::class);
        $this->call(Vs002Seeder::class);
        $this->call(Vs003Seeder::class);
        $this->call(Task010Seeder::class);
    }
}
