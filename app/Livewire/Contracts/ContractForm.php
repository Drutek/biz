<?php

namespace App\Livewire\Contracts;

use App\Enums\BillingFrequency;
use App\Enums\ContractStatus;
use App\Models\Contract;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ContractForm extends Component
{
    public ?Contract $contract = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public ?string $description = null;

    #[Validate('required|numeric|min:0')]
    public string|float $value = '';

    #[Validate('required|string')]
    public string $billing_frequency = '';

    #[Validate('required|date')]
    public string $start_date = '';

    public ?string $end_date = null;

    #[Validate('required|integer|min:0|max:100')]
    public int $probability = 100;

    #[Validate('required|string')]
    public string $status = '';

    public ?string $notes = null;

    public function mount(?Contract $contract = null): void
    {
        if ($contract?->exists) {
            $this->contract = $contract;
            $this->name = $contract->name;
            $this->description = $contract->description;
            $this->value = (string) $contract->value;
            $this->billing_frequency = $contract->billing_frequency->value;
            $this->start_date = $contract->start_date->format('Y-m-d');
            $this->end_date = $contract->end_date?->format('Y-m-d');
            $this->probability = $contract->probability;
            $this->status = $contract->status->value;
            $this->notes = $contract->notes;
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'value' => $this->value,
            'billing_frequency' => $this->billing_frequency,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'probability' => $this->probability,
            'status' => $this->status,
            'notes' => $this->notes,
        ];

        if ($this->contract?->exists) {
            $this->contract->update($data);
        } else {
            Contract::create($data);
        }

        $this->dispatch('contract-saved');
        $this->dispatch('close-modal');
    }

    public function render(): View
    {
        return view('livewire.contracts.contract-form', [
            'billingFrequencies' => BillingFrequency::cases(),
            'statuses' => ContractStatus::cases(),
        ]);
    }
}
