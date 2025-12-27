<?php

use App\Enums\MilestoneStatus;
use App\Enums\PricingModel;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Livewire\Products\Index;
use App\Livewire\Products\MilestoneForm;
use App\Livewire\Products\ProductForm;
use App\Livewire\Products\Show;
use App\Models\Product;
use App\Models\ProductMilestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Products Index', function () {
    it('can render products index page', function () {
        $this->actingAs($this->user)
            ->get('/products')
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('displays list of products', function () {
        Product::factory()->create(['name' => 'My Book']);
        Product::factory()->create(['name' => 'My SaaS']);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSee('My Book')
            ->assertSee('My SaaS');
    });

    it('can filter by status', function () {
        Product::factory()->create(['name' => 'Launched Product', 'status' => ProductStatus::Launched]);
        Product::factory()->create(['name' => 'Idea Product', 'status' => ProductStatus::Idea]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSee('Launched Product')
            ->assertSee('Idea Product')
            ->set('filterStatus', ProductStatus::Launched->value)
            ->assertSee('Launched Product')
            ->assertDontSee('Idea Product');
    });

    it('can filter by type', function () {
        Product::factory()->create(['name' => 'A Book', 'product_type' => ProductType::Book]);
        Product::factory()->create(['name' => 'A Course', 'product_type' => ProductType::Course]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSee('A Book')
            ->assertSee('A Course')
            ->set('filterType', ProductType::Book->value)
            ->assertSee('A Book')
            ->assertDontSee('A Course');
    });

    it('can delete a product', function () {
        $product = Product::factory()->create(['name' => 'To Delete']);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('delete', $product->id)
            ->assertDontSee('To Delete');

        expect(Product::count())->toBe(0);
    });
});

describe('Product Form', function () {
    it('can render product form', function () {
        Livewire::actingAs($this->user)
            ->test(ProductForm::class)
            ->assertOk();
    });

    it('can create a new product', function () {
        Livewire::actingAs($this->user)
            ->test(ProductForm::class)
            ->set('name', 'New Product')
            ->set('product_type', ProductType::Book->value)
            ->set('status', ProductStatus::Idea->value)
            ->set('pricing_model', PricingModel::OneTime->value)
            ->call('save')
            ->assertHasNoErrors();

        expect(Product::where('name', 'New Product')->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test(ProductForm::class)
            ->set('name', '')
            ->set('product_type', '')
            ->call('save')
            ->assertHasErrors(['name', 'product_type']);
    });

    it('can edit an existing product', function () {
        $product = Product::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($this->user)
            ->test(ProductForm::class, ['product' => $product])
            ->assertSet('name', 'Old Name')
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();

        expect($product->fresh()->name)->toBe('Updated Name');
    });

    it('can update revenue metrics', function () {
        $product = Product::factory()->create([
            'mrr' => 0,
            'total_revenue' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProductForm::class, ['product' => $product])
            ->set('mrr', '1500')
            ->set('total_revenue', '5000')
            ->set('subscriber_count', 50)
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        expect((float) $product->mrr)->toBe(1500.0);
        expect((float) $product->total_revenue)->toBe(5000.0);
        expect($product->subscriber_count)->toBe(50);
    });

    it('can set time investment fields', function () {
        $product = Product::factory()->create();

        Livewire::actingAs($this->user)
            ->test(ProductForm::class, ['product' => $product])
            ->set('hours_invested', '200')
            ->set('monthly_maintenance_hours', '10')
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        expect((float) $product->hours_invested)->toBe(200.0);
        expect((float) $product->monthly_maintenance_hours)->toBe(10.0);
    });
});

describe('Product Show', function () {
    it('can render product show page', function () {
        $product = Product::factory()->create();

        $this->actingAs($this->user)
            ->get("/products/{$product->id}")
            ->assertOk()
            ->assertSeeLivewire(Show::class);
    });

    it('displays product details', function () {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'total_revenue' => 5000,
            'hours_invested' => 100,
        ]);

        Livewire::actingAs($this->user)
            ->test(Show::class, ['product' => $product])
            ->assertSee('Test Product')
            ->assertSee('5,000')
            ->assertSee('100');
    });

    it('displays milestones', function () {
        $product = Product::factory()->create();
        ProductMilestone::factory()->create([
            'product_id' => $product->id,
            'title' => 'First Milestone',
        ]);

        Livewire::actingAs($this->user)
            ->test(Show::class, ['product' => $product])
            ->assertSee('First Milestone');
    });

    it('can delete a milestone', function () {
        $product = Product::factory()->create();
        $milestone = ProductMilestone::factory()->create([
            'product_id' => $product->id,
            'title' => 'To Delete',
        ]);

        Livewire::actingAs($this->user)
            ->test(Show::class, ['product' => $product])
            ->call('deleteMilestone', $milestone->id);

        expect(ProductMilestone::count())->toBe(0);
    });

    it('can complete a milestone', function () {
        $product = Product::factory()->create();
        $milestone = ProductMilestone::factory()->create([
            'product_id' => $product->id,
            'status' => MilestoneStatus::InProgress,
        ]);

        Livewire::actingAs($this->user)
            ->test(Show::class, ['product' => $product])
            ->call('completeMilestone', $milestone);

        expect($milestone->fresh()->status)->toBe(MilestoneStatus::Completed);
        expect($milestone->fresh()->completed_at)->not->toBeNull();
    });
});

describe('Milestone Form', function () {
    it('can render milestone form', function () {
        $product = Product::factory()->create();

        Livewire::actingAs($this->user)
            ->test(MilestoneForm::class, ['product' => $product])
            ->assertOk();
    });

    it('can create a new milestone', function () {
        $product = Product::factory()->create();

        Livewire::actingAs($this->user)
            ->test(MilestoneForm::class, ['product' => $product])
            ->set('title', 'New Milestone')
            ->set('status', MilestoneStatus::NotStarted->value)
            ->call('save')
            ->assertHasNoErrors();

        expect(ProductMilestone::where('title', 'New Milestone')->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        $product = Product::factory()->create();

        Livewire::actingAs($this->user)
            ->test(MilestoneForm::class, ['product' => $product])
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title']);
    });

    it('can edit an existing milestone', function () {
        $product = Product::factory()->create();
        $milestone = ProductMilestone::factory()->create([
            'product_id' => $product->id,
            'title' => 'Old Title',
        ]);

        Livewire::actingAs($this->user)
            ->test(MilestoneForm::class, ['product' => $product, 'milestone' => $milestone])
            ->assertSet('title', 'Old Title')
            ->set('title', 'Updated Title')
            ->call('save')
            ->assertHasNoErrors();

        expect($milestone->fresh()->title)->toBe('Updated Title');
    });

    it('assigns correct sort order to new milestones', function () {
        $product = Product::factory()->create();
        ProductMilestone::factory()->create(['product_id' => $product->id, 'sort_order' => 1]);
        ProductMilestone::factory()->create(['product_id' => $product->id, 'sort_order' => 2]);

        Livewire::actingAs($this->user)
            ->test(MilestoneForm::class, ['product' => $product])
            ->set('title', 'Third Milestone')
            ->set('status', MilestoneStatus::NotStarted->value)
            ->call('save');

        $newMilestone = ProductMilestone::where('title', 'Third Milestone')->first();
        expect($newMilestone->sort_order)->toBe(3);
    });
});

describe('Product Model', function () {
    it('calculates effective hourly rate correctly', function () {
        $product = Product::factory()->create([
            'total_revenue' => 1000,
            'hours_invested' => 50,
        ]);

        expect($product->effectiveHourlyRate())->toBe(20.0);
    });

    it('returns zero effective hourly rate when no hours invested', function () {
        $product = Product::factory()->create([
            'total_revenue' => 1000,
            'hours_invested' => 0,
        ]);

        expect($product->effectiveHourlyRate())->toBe(0.0);
    });

    it('detects launched status correctly', function () {
        $launched = Product::factory()->create(['status' => ProductStatus::Launched]);
        $idea = Product::factory()->create(['status' => ProductStatus::Idea]);

        expect($launched->isLaunched())->toBeTrue();
        expect($idea->isLaunched())->toBeFalse();
    });

    it('detects in development status correctly', function () {
        $inDev = Product::factory()->create(['status' => ProductStatus::InDevelopment]);
        $launched = Product::factory()->create(['status' => ProductStatus::Launched]);

        expect($inDev->isInDevelopment())->toBeTrue();
        expect($launched->isInDevelopment())->toBeFalse();
    });
});
