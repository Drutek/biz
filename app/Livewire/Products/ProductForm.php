<?php

namespace App\Livewire\Products;

use App\Enums\BillingFrequency;
use App\Enums\PricingModel;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ProductForm extends Component
{
    public ?Product $product = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public ?string $description = null;

    #[Validate('required|string')]
    public string $product_type = '';

    #[Validate('required|string')]
    public string $status = 'idea';

    #[Validate('nullable|numeric|min:0')]
    public string|float|null $price = null;

    #[Validate('required|string')]
    public string $pricing_model = 'one_time';

    public ?string $billing_frequency = null;

    #[Validate('nullable|numeric|min:0')]
    public string|float $mrr = '0';

    #[Validate('nullable|numeric|min:0')]
    public string|float $total_revenue = '0';

    #[Validate('nullable|integer|min:0')]
    public int $subscriber_count = 0;

    #[Validate('nullable|integer|min:0')]
    public int $units_sold = 0;

    #[Validate('nullable|numeric|min:0')]
    public string|float $hours_invested = '0';

    #[Validate('nullable|numeric|min:0')]
    public string|float $monthly_maintenance_hours = '0';

    public ?string $launched_at = null;

    public ?string $target_launch_date = null;

    #[Validate('nullable|url|max:500')]
    public ?string $url = null;

    public ?string $notes = null;

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->product = $product;
            $this->name = $product->name;
            $this->description = $product->description;
            $this->product_type = $product->product_type->value;
            $this->status = $product->status->value;
            $this->price = $product->price ? (string) $product->price : null;
            $this->pricing_model = $product->pricing_model->value;
            $this->billing_frequency = $product->billing_frequency?->value;
            $this->mrr = (string) $product->mrr;
            $this->total_revenue = (string) $product->total_revenue;
            $this->subscriber_count = $product->subscriber_count;
            $this->units_sold = $product->units_sold;
            $this->hours_invested = (string) $product->hours_invested;
            $this->monthly_maintenance_hours = (string) $product->monthly_maintenance_hours;
            $this->launched_at = $product->launched_at?->format('Y-m-d');
            $this->target_launch_date = $product->target_launch_date?->format('Y-m-d');
            $this->url = $product->url;
            $this->notes = $product->notes;
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'product_type' => $this->product_type,
            'status' => $this->status,
            'price' => $this->price ?: null,
            'pricing_model' => $this->pricing_model,
            'billing_frequency' => $this->billing_frequency ?: null,
            'mrr' => $this->mrr ?: 0,
            'total_revenue' => $this->total_revenue ?: 0,
            'subscriber_count' => $this->subscriber_count,
            'units_sold' => $this->units_sold,
            'hours_invested' => $this->hours_invested ?: 0,
            'monthly_maintenance_hours' => $this->monthly_maintenance_hours ?: 0,
            'launched_at' => $this->launched_at ?: null,
            'target_launch_date' => $this->target_launch_date ?: null,
            'url' => $this->url ?: null,
            'notes' => $this->notes,
        ];

        if ($this->product?->exists) {
            $this->product->update($data);
        } else {
            Product::create($data);
        }

        $this->dispatch('product-saved');
        $this->dispatch('close-modal');
    }

    public function render(): View
    {
        return view('livewire.products.product-form', [
            'productTypes' => ProductType::cases(),
            'statuses' => ProductStatus::cases(),
            'pricingModels' => PricingModel::cases(),
            'billingFrequencies' => BillingFrequency::cases(),
        ]);
    }
}
