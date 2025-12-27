<?php

namespace App\Livewire\Products;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public ?Product $editingProduct = null;

    public string $filterStatus = '';

    public string $filterType = '';

    public function render(): View
    {
        return view('livewire.products.index', [
            'products' => Product::query()
                ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
                ->when($this->filterType, fn ($q) => $q->where('product_type', $this->filterType))
                ->orderByDesc('created_at')
                ->get(),
            'statuses' => ProductStatus::cases(),
            'types' => ProductType::cases(),
        ]);
    }

    public function create(): void
    {
        $this->editingProduct = null;
        $this->showForm = true;
    }

    public function edit(Product $product): void
    {
        $this->editingProduct = $product;
        $this->showForm = true;
    }

    public function delete(int $productId): void
    {
        Product::destroy($productId);
    }

    #[On('close-modal')]
    #[On('product-saved')]
    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingProduct = null;
    }
}
