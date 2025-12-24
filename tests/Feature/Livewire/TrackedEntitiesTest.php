<?php

use App\Enums\EntityType;
use App\Livewire\TrackedEntities\Index;
use App\Livewire\TrackedEntities\TrackedEntityForm;
use App\Models\TrackedEntity;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('TrackedEntities Index', function () {
    it('renders the index page', function () {
        $this->get(route('tracked-entities.index'))
            ->assertSuccessful()
            ->assertSeeLivewire(Index::class);
    });

    it('displays tracked entities', function () {
        $entity = TrackedEntity::factory()->create(['name' => 'Acme Corporation']);

        Livewire::test(Index::class)
            ->assertSee('Acme Corporation');
    });

    it('filters entities by search term', function () {
        TrackedEntity::factory()->create(['name' => 'Acme Corporation']);
        TrackedEntity::factory()->create(['name' => 'Widget Inc']);

        Livewire::test(Index::class)
            ->set('search', 'Acme')
            ->assertSee('Acme Corporation')
            ->assertDontSee('Widget Inc');
    });

    it('filters entities by type', function () {
        TrackedEntity::factory()->create(['name' => 'Tech Corp', 'entity_type' => EntityType::Company]);
        TrackedEntity::factory()->create(['name' => 'AI Trends', 'entity_type' => EntityType::Topic]);

        Livewire::test(Index::class)
            ->set('typeFilter', EntityType::Company->value)
            ->assertSee('Tech Corp')
            ->assertDontSee('AI Trends');
    });

    it('can delete an entity', function () {
        $entity = TrackedEntity::factory()->create(['name' => 'Delete Me']);

        Livewire::test(Index::class)
            ->call('delete', $entity->id)
            ->assertDontSee('Delete Me');

        expect(TrackedEntity::find($entity->id))->toBeNull();
    });

    it('opens create modal', function () {
        Livewire::test(Index::class)
            ->call('create')
            ->assertDispatched('open-entity-modal');
    });

    it('opens edit modal', function () {
        $entity = TrackedEntity::factory()->create();

        Livewire::test(Index::class)
            ->call('edit', $entity->id)
            ->assertDispatched('open-entity-modal');
    });
});

describe('TrackedEntityForm', function () {
    it('renders the form', function () {
        Livewire::test(TrackedEntityForm::class)
            ->assertSee('Name')
            ->assertSee('Type')
            ->assertSee('Search Query');
    });

    it('can create a new entity', function () {
        Livewire::test(TrackedEntityForm::class)
            ->set('name', 'New Company')
            ->set('entity_type', EntityType::Company->value)
            ->set('search_query', 'new company innovation')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('entity-saved');

        expect(TrackedEntity::where('name', 'New Company')->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(TrackedEntityForm::class)
            ->set('name', '')
            ->set('entity_type', '')
            ->call('save')
            ->assertHasErrors(['name', 'entity_type']);
    });

    it('can edit an existing entity', function () {
        $entity = TrackedEntity::factory()->create([
            'name' => 'Old Name',
            'entity_type' => EntityType::Company,
        ]);

        Livewire::test(TrackedEntityForm::class)
            ->call('load', $entity->id)
            ->assertSet('name', 'Old Name')
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();

        expect($entity->fresh()->name)->toBe('Updated Name');
    });

    it('resets form when closed', function () {
        Livewire::test(TrackedEntityForm::class)
            ->set('name', 'Test')
            ->call('resetForm')
            ->assertSet('name', '')
            ->assertSet('entityId', null);
    });
});
