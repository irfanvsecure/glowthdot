<?php

namespace Tests;

use App\Support\Settings;
use Database\Seeders\SiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** The site's content is imported once per run (database/testing.sqlite); each test runs in a transaction */
    protected $seeder = SiteSeeder::class;

    protected function setUp(): void
    {
        $file = dirname(__DIR__).'/database/testing.sqlite';
        if (! is_file($file)) {
            touch($file);
        }
        parent::setUp();
        Settings::forget();
    }

    protected function shouldSeed(): bool
    {
        return true;
    }
}
