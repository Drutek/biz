<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\BusinessEventRecorder;
use App\Services\ProactiveAdvisor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateDailyInsights implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct() {}

    public function handle(ProactiveAdvisor $advisor, BusinessEventRecorder $recorder): void
    {
        Log::info('Starting daily insights generation');

        $users = User::query()
            ->whereHas('preferences', function ($query) {
                $query->where('proactive_insights_enabled', true);
            })
            ->get();

        foreach ($users as $user) {
            try {
                $insight = $advisor->generateDailyAnalysis($user);

                if ($insight) {
                    $recorder->recordAiInsight($insight, $user);
                    Log::info("Daily insight generated for user {$user->id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to generate daily insight for user {$user->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Daily insights generation completed');
    }
}
