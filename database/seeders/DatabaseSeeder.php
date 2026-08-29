<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The first administrator account is created via the
     * `php artisan app:create-admin` command, not a seeder,
     * so admin credentials are never hardcoded in source control.
     */
    public function run(): void
    {
        //
    }
}
