<?php

use App\Enums\ExpenseFrequency;
use App\Livewire\Expenses\ExpenseForm;
use App\Livewire\Expenses\Index;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Expenses Index', function () {
    it('can render expenses index page', function () {
        $this->actingAs($this->user)
            ->get('/expenses')
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('displays list of expenses', function () {
        Expense::factory()->create(['name' => 'Office Rent']);
        Expense::factory()->create(['name' => 'Software Subscription']);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSee('Office Rent')
            ->assertSee('Software Subscription');
    });

    it('can delete an expense', function () {
        $expense = Expense::factory()->create(['name' => 'To Delete']);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('delete', $expense->id)
            ->assertDontSee('To Delete');

        expect(Expense::count())->toBe(0);
    });
});

describe('Expense Form', function () {
    it('can render expense form', function () {
        Livewire::actingAs($this->user)
            ->test(ExpenseForm::class)
            ->assertOk();
    });

    it('can create a new expense', function () {
        Livewire::actingAs($this->user)
            ->test(ExpenseForm::class)
            ->set('name', 'New Expense')
            ->set('amount', 500)
            ->set('frequency', ExpenseFrequency::Monthly->value)
            ->set('category', 'software')
            ->set('start_date', '2024-01-15')
            ->call('save')
            ->assertHasNoErrors();

        expect(Expense::where('name', 'New Expense')->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test(ExpenseForm::class)
            ->set('name', '')
            ->set('amount', '')
            ->call('save')
            ->assertHasErrors(['name', 'amount', 'frequency', 'category', 'start_date']);
    });

    it('can edit an existing expense', function () {
        $expense = Expense::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($this->user)
            ->test(ExpenseForm::class, ['expense' => $expense])
            ->assertSet('name', 'Old Name')
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();

        expect($expense->fresh()->name)->toBe('Updated Name');
    });
});
