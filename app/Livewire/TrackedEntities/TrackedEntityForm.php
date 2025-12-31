<?php

namespace App\Livewire\TrackedEntities;

use App\Enums\EntityType;
use App\Models\TrackedEntity;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class TrackedEntityForm extends Component
{
    public ?int $entityId = null;

    public string $name = '';

    public string $entity_type = '';

    public string $search_query = '';

    public string $negative_terms = '';

    public bool $is_active = true;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'entity_type' => 'required|string|in:'.implode(',', array_column(EntityType::cases(), 'value')),
            'search_query' => 'nullable|string',
            'negative_terms' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function render(): View
    {
        return view('livewire.tracked-entities.tracked-entity-form', [
            'types' => EntityType::cases(),
        ]);
    }

    #[On('open-entity-modal')]
    public function load(?int $entityId = null): void
    {
        if ($entityId) {
            $entity = TrackedEntity::findOrFail($entityId);
            $this->entityId = $entity->id;
            $this->name = $entity->name;
            $this->entity_type = $entity->entity_type->value;
            $this->search_query = $entity->search_query ?? '';
            $this->negative_terms = $entity->negative_terms ?? '';
            $this->is_active = $entity->is_active;
        } else {
            $this->resetForm();
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'entity_type' => $this->entity_type,
            'search_query' => $this->search_query ?: $this->name.' news',
            'negative_terms' => $this->negative_terms ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->entityId) {
            TrackedEntity::where('id', $this->entityId)->update($data);
        } else {
            TrackedEntity::create($data);
        }

        $this->dispatch('entity-saved');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->entityId = null;
        $this->name = '';
        $this->entity_type = '';
        $this->search_query = '';
        $this->negative_terms = '';
        $this->is_active = true;
        $this->resetValidation();
    }
}
