<?php

namespace App\Livewire\Standup;

use App\Models\DailyStandup;
use App\Models\Task;
use App\Services\StandupGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Today extends Component
{
    public ?DailyStandup $standup = null;

    public bool $isWorkDay = true;

    public bool $interactiveStandupEnabled = true;

    public function mount(StandupGenerator $generator): void
    {
        $user = Auth::user();
        $preferences = $user->preferences ?? $user->getOrCreatePreferences();

        $this->isWorkDay = $preferences->isWorkDay();
        $this->interactiveStandupEnabled = $preferences->interactive_standup_enabled;

        $this->standup = $user->standups()
            ->forDate(now())
            ->first();

        if (! $this->standup) {
            $this->standup = $generator->generate($user);
        }

        if ($this->standup && ! $this->standup->viewed_at) {
            $this->standup->markAsViewed();
        }
    }

    #[On('standup-entry-completed')]
    public function refreshComponent(): void
    {
        // Refresh the component when standup entry is completed
    }

    public function render(): View
    {
        $suggestedTasksCount = Task::where('user_id', Auth::id())
            ->suggested()
            ->count();

        return view('livewire.standup.today', [
            'suggestedTasksCount' => $suggestedTasksCount,
        ]);
    }
}
