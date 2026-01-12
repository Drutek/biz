<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Social\LinkedInPostService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateLinkedInPostsJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

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
    public function handle(LinkedInPostService $service): void
    {
        Log::info('Starting LinkedIn posts generation job');

        $users = User::query()
            ->whereHas('preferences', fn ($q) => $q->where('linkedin_posts_enabled', true))
            ->get();

        if ($users->isEmpty()) {
            // Fall back to all users if no preferences exist yet
            $users = User::all();
        }

        foreach ($users as $user) {
            $this->generateForUser($user, $service);
        }

        Log::info('LinkedIn posts generation job completed');
    }

    protected function generateForUser(User $user, LinkedInPostService $service): void
    {
        if (! $service->shouldGenerateForUser($user)) {
            Log::info("Skipping LinkedIn post generation for user {$user->id} (not due yet)");

            return;
        }

        try {
            $posts = $service->generateBatch($user);

            if ($posts->isNotEmpty()) {
                Log::info("Generated {$posts->count()} LinkedIn posts for user {$user->id}");
            } else {
                Log::info("No LinkedIn posts generated for user {$user->id}");
            }
        } catch (\Throwable $e) {
            Log::error("Failed to generate LinkedIn posts for user {$user->id}: {$e->getMessage()}");
        }
    }
}
