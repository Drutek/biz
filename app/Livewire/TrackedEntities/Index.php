<?php

namespace App\Livewire\TrackedEntities;

use App\Enums\EntityType;
use App\Models\TrackedEntity;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public string $search = '';

    public string $typeFilter = '';

    public bool $showForm = false;

    public ?TrackedEntity $editingEntity = null;

    public function render(): View
    {
        $query = TrackedEntity::query()
            ->withCount('newsItems')
            ->orderByDesc('created_at');

        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        if ($this->typeFilter) {
            $query->where('entity_type', $this->typeFilter);
        }

        return view('livewire.tracked-entities.index', [
            'entities' => $query->get(),
            'types' => EntityType::cases(),
        ]);
    }

    public function create(): void
    {
        $this->editingEntity = null;
        $this->showForm = true;
        $this->dispatch('open-entity-modal');
    }

    public function edit(int $entityId): void
    {
        $this->editingEntity = TrackedEntity::find($entityId);
        $this->showForm = true;
        $this->dispatch('open-entity-modal', entityId: $entityId);
    }

    public function delete(int $entityId): void
    {
        TrackedEntity::destroy($entityId);
    }

    #[On('close-modal')]
    #[On('entity-saved')]
    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingEntity = null;
    }
}
