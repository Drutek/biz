<?php

namespace App\Livewire\Advisor;

use App\Services\Embedding\VectorSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SemanticSearch extends Component
{
    public string $query = '';

    public bool $isSearching = false;

    /**
     * @var Collection<int, object>
     */
    public Collection $results;

    public function mount(): void
    {
        $this->results = collect();
    }

    public function search(): void
    {
        if (strlen($this->query) < 3) {
            $this->results = collect();

            return;
        }

        $this->isSearching = true;

        $vectorSearch = app(VectorSearchService::class);

        $this->results = $vectorSearch->searchAdvisoryMessages(
            userId: Auth::id(),
            query: $this->query,
            limit: 20,
            threshold: 0.35
        );

        $this->isSearching = false;
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) >= 3) {
            $this->search();
        } else {
            $this->results = collect();
        }
    }

    public function clear(): void
    {
        $this->query = '';
        $this->results = collect();
    }

    #[Computed]
    public function hasResults(): bool
    {
        return $this->results->isNotEmpty();
    }

    public function render(): View
    {
        return view('livewire.advisor.semantic-search');
    }
}
