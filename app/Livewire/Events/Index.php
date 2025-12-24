<?php

namespace App\Livewire\Events;

use App\Enums\EventCategory;
use App\Enums\EventSignificance;
use App\Models\BusinessEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $category = '';

    #[Url]
    public string $significance = '';

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSignificance(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<BusinessEvent>
     */
    public function getEventsProperty(): LengthAwarePaginator
    {
        $query = Auth::user()->businessEvents();

        if ($this->category) {
            $query->byCategory(EventCategory::from($this->category));
        }

        if ($this->significance) {
            $query->bySignificance(EventSignificance::from($this->significance));
        }

        return $query->orderByDesc('occurred_at')->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.events.index', [
            'categories' => EventCategory::cases(),
            'significances' => EventSignificance::cases(),
        ]);
    }
}
