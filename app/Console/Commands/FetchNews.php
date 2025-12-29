<?php

namespace App\Console\Commands;

use App\Services\News\SerpApiService;
use Illuminate\Console\Command;

class FetchNews extends Command
{
    protected $signature = 'news:fetch';

    protected $description = 'Fetch news for all tracked entities';

    public function handle(SerpApiService $service): int
    {
        $this->info('Fetching news for tracked entities...');

        $service->fetchAll();

        $this->info('Done!');

        return Command::SUCCESS;
    }
}
