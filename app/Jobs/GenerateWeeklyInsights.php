<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\BusinessEventRecorder;
use App\Services\ProactiveAdvisor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateWeeklyInsights implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct() {}

    public function handle(ProactiveAdvisor $advisor, BusinessEventRecorder $recorder): void
    {
        Log::info('Starting weekly insights generation');

        $users = User::query()
            ->whereHas('preferences', function ($query) {
                $query->where('proactive_insights_enabled', true)
                    ->whereIn('insight_frequency', ['daily', 'weekly']);
            })
            ->get();

        foreach ($users as $user) {
            try {
                $weeklyInsight = $advisor->generateWeeklyAnalysis($user);

                if ($weeklyInsight) {
                    $recorder->recordAiInsight($weeklyInsight, $user);
                    Log::info("Weekly insight generated for user {$user->id}");
                }

                $opportunityInsight = $advisor->identifyOpportunities($user);

                if ($opportunityInsight) {
                    $recorder->recordAiInsight($opportunityInsight, $user);
                    Log::info("Opportunity insight generated for user {$user->id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to generate weekly insights for user {$user->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Weekly insights generation completed');
    }
}
