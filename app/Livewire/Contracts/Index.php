<?php

namespace App\Livewire\Contracts;

use App\Models\Contract;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public ?Contract $editingContract = null;

    public function render(): View
    {
        return view('livewire.contracts.index', [
            'contracts' => Contract::query()
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function create(): void
    {
        $this->editingContract = null;
        $this->showForm = true;
    }

    public function edit(Contract $contract): void
    {
        $this->editingContract = $contract;
        $this->showForm = true;
    }

    public function delete(int $contractId): void
    {
        Contract::destroy($contractId);
    }

    #[On('close-modal')]
    #[On('contract-saved')]
    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingContract = null;
    }
}
