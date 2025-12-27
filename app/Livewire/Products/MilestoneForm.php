<?php

namespace App\Livewire\Products;

use App\Enums\MilestoneStatus;
use App\Models\Product;
use App\Models\ProductMilestone;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class MilestoneForm extends Component
{
    public Product $product;

    public ?ProductMilestone $milestone = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    public ?string $description = null;

    #[Validate('required|string')]
    public string $status = 'not_started';

    public ?string $target_date = null;

    public function mount(Product $product, ?ProductMilestone $milestone = null): void
    {
        $this->product = $product;

        if ($milestone?->exists) {
            $this->milestone = $milestone;
            $this->title = $milestone->title;
            $this->description = $milestone->description;
            $this->status = $milestone->status->value;
            $this->target_date = $milestone->target_date?->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'target_date' => $this->target_date ?: null,
        ];

        if ($this->milestone?->exists) {
            $this->milestone->update($data);
        } else {
            $maxSort = $this->product->milestones()->max('sort_order') ?? 0;
            $this->product->milestones()->create([
                ...$data,
                'sort_order' => $maxSort + 1,
            ]);
        }

        $this->dispatch('milestone-saved');
        $this->dispatch('close-modal');
    }

    public function render(): View
    {
        return view('livewire.products.milestone-form', [
            'statuses' => MilestoneStatus::cases(),
        ]);
    }
}
