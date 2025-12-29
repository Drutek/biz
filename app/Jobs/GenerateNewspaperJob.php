<?php

namespace App\Jobs;

use App\Models\NewspaperEdition;
use App\Models\TrackedEntity;
use App\Models\User;
use App\Services\News\NewspaperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateNewspaperJob implements ShouldQueue
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
    public function handle(NewspaperService $newspaperService): void
    {
        Log::info('Starting newspaper generation job');

        // Only generate newspapers if there are tracked entities
        if (TrackedEntity::active()->doesntExist()) {
            Log::info('No active tracked entities, skipping newspaper generation');

            return;
        }

        $users = User::all();

        foreach ($users as $user) {
            $this->generateForUser($user, $newspaperService);
        }

        Log::info('Newspaper generation job completed');
    }

    protected function generateForUser(User $user, NewspaperService $newspaperService): void
    {
        // Check if edition already exists for today
        $existingEdition = NewspaperEdition::todayForUser($user);

        if ($existingEdition) {
            Log::info("Newspaper edition already exists for user {$user->id}");

            return;
        }

        try {
            $edition = $newspaperService->generateForUser($user);

            if ($edition) {
                Log::info("Generated newspaper edition for user {$user->id}");
            } else {
                Log::info("No newspaper edition generated for user {$user->id} (no news)");
            }
        } catch (\Throwable $e) {
            Log::error("Failed to generate newspaper for user {$user->id}: {$e->getMessage()}");
        }
    }
}
