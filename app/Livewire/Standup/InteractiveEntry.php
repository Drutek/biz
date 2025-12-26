<?php

namespace App\Livewire\Standup;

use App\Models\DailyStandup;
use App\Models\StandupEntry;
use App\Services\InteractiveStandupService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InteractiveEntry extends Component
{
    public ?DailyStandup $standup = null;

    public ?StandupEntry $entry = null;

    public string $yesterdayAccomplished = '';

    public string $todayPlanned = '';

    public string $blockers = '';

    /** @var array<int, string> */
    public array $followUpQuestions = [];

    /** @var array<int, string> */
    public array $followUpResponses = [];

    public string $aiAnalysis = '';

    public bool $showFollowUp = false;

    public bool $isSubmitting = false;

    public bool $isSkipped = false;

    public function mount(DailyStandup $standup): void
    {
        $this->standup = $standup;

        $this->entry = StandupEntry::query()
            ->where('user_id', Auth::id())
            ->where('daily_standup_id', $standup->id)
            ->first();

        if ($this->entry) {
            $this->yesterdayAccomplished = $this->entry->yesterday_accomplished ?? '';
            $this->todayPlanned = $this->entry->today_planned ?? '';
            $this->blockers = $this->entry->blockers ?? '';
            $this->followUpQuestions = $this->entry->ai_follow_up_questions ?? [];
            $this->followUpResponses = $this->entry->ai_follow_up_responses ?? [];
            $this->aiAnalysis = $this->entry->ai_analysis ?? '';

            if ($this->entry->isComplete()) {
                $this->showFollowUp = false;
            } elseif (! empty($this->followUpQuestions)) {
                $this->showFollowUp = true;
            }
        }
    }

    public function submitEntry(InteractiveStandupService $service): void
    {
        $this->isSubmitting = true;

        $this->entry = StandupEntry::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'daily_standup_id' => $this->standup->id,
            ],
            [
                'yesterday_accomplished' => $this->yesterdayAccomplished,
                'today_planned' => $this->todayPlanned,
                'blockers' => $this->blockers ?: null,
            ]
        );

        $questions = $service->generateFollowUpQuestions($this->entry);

        if (! empty($questions)) {
            $this->followUpQuestions = $questions;
            $this->followUpResponses = array_fill(0, count($questions), '');
            $this->entry->update([
                'ai_follow_up_questions' => $questions,
            ]);
            $this->showFollowUp = true;
        } else {
            $this->finishEntry($service);
        }

        $this->isSubmitting = false;
    }

    public function submitFollowUp(InteractiveStandupService $service): void
    {
        $this->isSubmitting = true;

        $this->entry->update([
            'ai_follow_up_responses' => $this->followUpResponses,
        ]);

        $this->finishEntry($service);

        $this->isSubmitting = false;
    }

    public function skipFollowUp(InteractiveStandupService $service): void
    {
        $this->finishEntry($service);
    }

    public function skip(): void
    {
        $this->isSkipped = true;
    }

    protected function finishEntry(InteractiveStandupService $service): void
    {
        $analysis = $service->generateAnalysis($this->entry);

        $this->entry->update([
            'ai_analysis' => $analysis,
            'submitted_at' => now(),
        ]);

        $this->aiAnalysis = $analysis;
        $this->showFollowUp = false;

        // Extract tasks from today's plans
        $service->extractTasksFromToday($this->entry);

        $this->dispatch('standup-entry-completed');
    }

    public function render(): View
    {
        return view('livewire.standup.interactive-entry');
    }
}
