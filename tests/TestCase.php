<?php

namespace Tests;

use App\Services\OgCards;
use App\Support\Settings;
use Database\Seeders\SiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** The site's content is imported once per run (database/testing.sqlite); each test runs in a transaction */
    protected $seeder = SiteSeeder::class;

    /** Share cards in public/images/og before the test: the ones a test makes are removed afterwards */
    private array $cardsBefore = [];

    protected function setUp(): void
    {
        $file = dirname(__DIR__).'/database/testing.sqlite';
        if (! is_file($file)) {
            touch($file);
        }
        $this->cardsBefore = glob(dirname(__DIR__).'/public'.OgCards::DIR.'/*.png') ?: [];
        parent::setUp();
        Settings::forget();
    }

    protected function tearDown(): void
    {
        foreach (array_diff(glob(dirname(__DIR__).'/public'.OgCards::DIR.'/*.png') ?: [], $this->cardsBefore) as $card) {
            @unlink($card);
        }
        parent::tearDown();
    }

    protected function shouldSeed(): bool
    {
        return true;
    }
}
