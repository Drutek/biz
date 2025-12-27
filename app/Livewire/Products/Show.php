<?php

namespace App\Livewire\Products;

use App\Enums\MilestoneStatus;
use App\Models\Product;
use App\Models\ProductMilestone;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Product $product;

    public bool $showMilestoneForm = false;

    public ?ProductMilestone $editingMilestone = null;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['milestones' => fn ($q) => $q->orderBy('sort_order')]);
    }

    public function createMilestone(): void
    {
        $this->editingMilestone = null;
        $this->showMilestoneForm = true;
    }

    public function editMilestone(ProductMilestone $milestone): void
    {
        $this->editingMilestone = $milestone;
        $this->showMilestoneForm = true;
    }

    public function deleteMilestone(int $milestoneId): void
    {
        ProductMilestone::destroy($milestoneId);
        $this->product->refresh();
    }

    public function completeMilestone(ProductMilestone $milestone): void
    {
        $milestone->markComplete();
        $this->product->refresh();
    }

    #[On('close-modal')]
    #[On('milestone-saved')]
    public function closeMilestoneForm(): void
    {
        $this->showMilestoneForm = false;
        $this->editingMilestone = null;
        $this->product->refresh();
    }

    public function render(): View
    {
        return view('livewire.products.show', [
            'milestoneStatuses' => MilestoneStatus::cases(),
            'revenueSnapshots' => $this->product->revenueSnapshots()
                ->orderByDesc('recorded_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
