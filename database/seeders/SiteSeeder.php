<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * The site's content from database/import (tests use it; a new install runs site:import).
 */
class SiteSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('site:import', ['--fresh' => true]);
    }
}
