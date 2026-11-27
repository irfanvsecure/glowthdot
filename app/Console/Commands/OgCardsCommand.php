<?php

namespace App\Console\Commands;

use App\Services\OgCards;
use Illuminate\Console\Command;

class OgCardsCommand extends Command
{
    protected $signature = 'site:og-cards {--force : Also pages that have a share image of their own}';

    protected $description = 'Make a share image (og:image) for every page that has none or shares one with another page';

    public function handle(OgCards $cards): int
    {
        $done = $cards->refreshAll((bool) $this->option('force'));
        foreach ($done as $path => $card) {
            $this->line("$path  $card");
        }
        $this->info(count($done).' share images made.');

        return self::SUCCESS;
    }
}
