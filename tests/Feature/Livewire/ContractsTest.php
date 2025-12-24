<?php

use App\Enums\BillingFrequency;
use App\Enums\ContractStatus;
use App\Livewire\Contracts\ContractForm;
use App\Livewire\Contracts\Index;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Contracts Index', function () {
    it('can render contracts index page', function () {
        $this->actingAs($this->user)
            ->get('/contracts')
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('displays list of contracts', function () {
        Contract::factory()->create(['name' => 'Acme Corp']);
        Contract::factory()->create(['name' => 'Beta Inc']);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSee('Acme Corp')
            ->assertSee('Beta Inc');
    });

    it('can delete a contract', function () {
        $contract = Contract::factory()->create(['name' => 'To Delete']);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('delete', $contract->id)
            ->assertDontSee('To Delete');

        expect(Contract::count())->toBe(0);
    });
});

describe('Contract Form', function () {
    it('can render contract form', function () {
        Livewire::actingAs($this->user)
            ->test(ContractForm::class)
            ->assertOk();
    });

    it('can create a new contract', function () {
        Livewire::actingAs($this->user)
            ->test(ContractForm::class)
            ->set('name', 'New Contract')
            ->set('value', 5000)
            ->set('billing_frequency', BillingFrequency::Monthly->value)
            ->set('status', ContractStatus::Confirmed->value)
            ->set('start_date', '2024-01-15')
            ->set('probability', 100)
            ->call('save')
            ->assertHasNoErrors();

        expect(Contract::where('name', 'New Contract')->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test(ContractForm::class)
            ->set('name', '')
            ->set('value', '')
            ->call('save')
            ->assertHasErrors(['name', 'value', 'billing_frequency', 'status', 'start_date']);
    });

    it('can edit an existing contract', function () {
        $contract = Contract::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($this->user)
            ->test(ContractForm::class, ['contract' => $contract])
            ->assertSet('name', 'Old Name')
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();

        expect($contract->fresh()->name)->toBe('Updated Name');
    });

    it('validates probability is between 0 and 100', function () {
        Livewire::actingAs($this->user)
            ->test(ContractForm::class)
            ->set('name', 'Test')
            ->set('value', 5000)
            ->set('billing_frequency', BillingFrequency::Monthly->value)
            ->set('status', ContractStatus::Pipeline->value)
            ->set('start_date', '2024-01-15')
            ->set('probability', 150)
            ->call('save')
            ->assertHasErrors(['probability']);
    });
});
