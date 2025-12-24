<?php

namespace App\Livewire\Events;

use App\Enums\EventCategory;
use App\Enums\EventSignificance;
use App\Services\BusinessEventRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Create extends Component
{
    public string $title = '';

    public string $description = '';

    public string $category = 'milestone';

    public string $significance = 'medium';

    public string $occurred_at = '';

    public function mount(): void
    {
        $this->occurred_at = now()->format('Y-m-d\TH:i');
    }

    public function save(BusinessEventRecorder $recorder): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'required|in:'.implode(',', array_column(EventCategory::cases(), 'value')),
            'significance' => 'required|in:'.implode(',', array_column(EventSignificance::cases(), 'value')),
            'occurred_at' => 'required|date',
        ]);

        $recorder->recordManualEvent(
            user: Auth::user(),
            title: $this->title,
            description: $this->description,
            category: EventCategory::from($this->category),
            significance: EventSignificance::from($this->significance),
            occurredAt: \Carbon\Carbon::parse($this->occurred_at),
        );

        session()->flash('message', 'Event logged successfully.');

        $this->redirect(route('events.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.events.create', [
            'categories' => EventCategory::cases(),
            'significances' => EventSignificance::cases(),
        ]);
    }
}
