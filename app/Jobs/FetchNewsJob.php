<?php

namespace App\Jobs;

use App\Services\News\SerpApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchNewsJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SerpApiService $serpApiService): void
    {
        Log::info('Starting news fetch job');

        try {
            $serpApiService->fetchAll();
            Log::info('News fetch job completed successfully');
        } catch (\Exception $e) {
            Log::error('News fetch job failed: '.$e->getMessage());
            throw $e;
        }
    }
}
