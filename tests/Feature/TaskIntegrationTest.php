<?php

use App\Livewire\Settings\TaskIntegrations;
use App\Livewire\Tasks\Index;
use App\Models\Task;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\TaskIntegration\AsanaProvider;
use App\Services\TaskIntegration\ExportResult;
use App\Services\TaskIntegration\JiraProvider;
use App\Services\TaskIntegration\NotionProvider;
use App\Services\TaskIntegration\TaskIntegrationManager;
use App\Services\TaskIntegration\TrelloProvider;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->preferences = UserPreference::create([
        'user_id' => $this->user->id,
        'standup_email_enabled' => false,
        'standup_email_time' => '07:00',
        'standup_email_timezone' => 'UTC',
        'in_app_notifications_enabled' => true,
        'proactive_insights_enabled' => true,
        'runway_alert_threshold' => 3,
    ]);
});

describe('ExportResult', function () {
    it('creates success result with external id and url', function () {
        $result = ExportResult::success('abc123', 'https://example.com/task');

        expect($result->success)->toBeTrue()
            ->and($result->externalId)->toBe('abc123')
            ->and($result->url)->toBe('https://example.com/task')
            ->and($result->error)->toBeNull();
    });

    it('creates failure result with error message', function () {
        $result = ExportResult::failure('Something went wrong');

        expect($result->success)->toBeFalse()
            ->and($result->externalId)->toBeNull()
            ->and($result->error)->toBe('Something went wrong');
    });

    it('generates metadata for successful result', function () {
        $result = ExportResult::success('abc123', 'https://example.com');
        $metadata = $result->toMetadata('notion');

        expect($metadata)->toHaveKey('provider', 'notion')
            ->and($metadata)->toHaveKey('external_id', 'abc123')
            ->and($metadata)->toHaveKey('url', 'https://example.com')
            ->and($metadata)->toHaveKey('exported_at');
    });

    it('returns empty metadata for failed result', function () {
        $result = ExportResult::failure('error');
        $metadata = $result->toMetadata('notion');

        expect($metadata)->toBeEmpty();
    });
});

describe('Task Export Helpers', function () {
    it('detects when task is not exported', function () {
        $task = Task::factory()->accepted()->create([
            'user_id' => $this->user->id,
        ]);

        expect($task->isExported())->toBeFalse()
            ->and($task->getExternalUrl())->toBeNull()
            ->and($task->getExportedProvider())->toBeNull();
    });

    it('detects when task is exported', function () {
        $task = Task::factory()->accepted()->create([
            'user_id' => $this->user->id,
            'metadata' => [
                'external_task' => [
                    'provider' => 'notion',
                    'external_id' => 'page123',
                    'url' => 'https://notion.so/page123',
                    'exported_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        expect($task->isExported())->toBeTrue()
            ->and($task->getExternalUrl())->toBe('https://notion.so/page123')
            ->and($task->getExportedProvider())->toBe('notion')
            ->and($task->getExternalId())->toBe('page123');
    });
});

describe('TaskIntegrationManager', function () {
    it('returns all available providers', function () {
        $manager = new TaskIntegrationManager;
        $providers = $manager->getProviders();

        expect($providers)->toHaveCount(4)
            ->and(array_keys($providers))->toBe(['notion', 'trello', 'jira', 'asana']);
    });

    it('gets specific provider by key', function () {
        $manager = new TaskIntegrationManager;

        expect($manager->getProvider('notion'))->toBeInstanceOf(NotionProvider::class)
            ->and($manager->getProvider('trello'))->toBeInstanceOf(TrelloProvider::class)
            ->and($manager->getProvider('jira'))->toBeInstanceOf(JiraProvider::class)
            ->and($manager->getProvider('asana'))->toBeInstanceOf(AsanaProvider::class)
            ->and($manager->getProvider('invalid'))->toBeNull();
    });

    it('returns null when no provider configured', function () {
        $manager = new TaskIntegrationManager;

        expect($manager->getActiveProvider($this->user))->toBeNull()
            ->and($manager->hasConfiguredIntegration($this->user))->toBeFalse();
    });

    it('returns active provider when configured', function () {
        $this->preferences->update([
            'task_integration_provider' => 'notion',
            'task_integration_config' => [
                'api_key' => 'test-key',
                'database_id' => 'test-db',
            ],
        ]);

        $manager = new TaskIntegrationManager;

        expect($manager->getActiveProvider($this->user))->toBeInstanceOf(NotionProvider::class)
            ->and($manager->hasConfiguredIntegration($this->user))->toBeTrue()
            ->and($manager->getActiveProviderName($this->user))->toBe('Notion');
    });

    it('returns false for incomplete configuration', function () {
        $this->preferences->update([
            'task_integration_provider' => 'notion',
            'task_integration_config' => [
                'api_key' => 'test-key',
                // Missing database_id
            ],
        ]);

        $manager = new TaskIntegrationManager;

        expect($manager->hasConfiguredIntegration($this->user))->toBeFalse();
    });
});

describe('Provider Interface', function () {
    it('notion provider has correct config', function () {
        $provider = new NotionProvider;

        expect($provider->getKey())->toBe('notion')
            ->and($provider->getName())->toBe('Notion')
            ->and($provider->isImplemented())->toBeTrue()
            ->and($provider->getConfigFields())->toHaveCount(2);
    });

    it('trello provider has correct config', function () {
        $provider = new TrelloProvider;

        expect($provider->getKey())->toBe('trello')
            ->and($provider->getName())->toBe('Trello')
            ->and($provider->isImplemented())->toBeTrue()
            ->and($provider->getConfigFields())->toHaveCount(3);
    });

    it('jira provider is not implemented', function () {
        $provider = new JiraProvider;

        expect($provider->getKey())->toBe('jira')
            ->and($provider->isImplemented())->toBeFalse();
    });

    it('asana provider is not implemented', function () {
        $provider = new AsanaProvider;

        expect($provider->getKey())->toBe('asana')
            ->and($provider->isImplemented())->toBeFalse();
    });

    it('stub providers return coming soon error', function () {
        $task = Task::factory()->accepted()->create([
            'user_id' => $this->user->id,
        ]);

        $jira = new JiraProvider;
        $asana = new AsanaProvider;

        expect($jira->createTask($task, []))->toBeInstanceOf(ExportResult::class)
            ->and($jira->createTask($task, [])->success)->toBeFalse()
            ->and($jira->createTask($task, [])->error)->toContain('coming soon');

        expect($asana->createTask($task, []))->toBeInstanceOf(ExportResult::class)
            ->and($asana->createTask($task, [])->success)->toBeFalse();
    });
});

describe('UserPreference Integration', function () {
    it('stores task integration provider', function () {
        $this->preferences->update([
            'task_integration_provider' => 'trello',
            'task_integration_config' => ['api_key' => 'key', 'token' => 'tok', 'list_id' => 'list'],
        ]);

        $this->preferences->refresh();

        expect($this->preferences->task_integration_provider)->toBe('trello')
            ->and($this->preferences->task_integration_config)->toBeArray()
            ->and($this->preferences->hasTaskIntegration())->toBeTrue();
    });

    it('returns empty config when not set', function () {
        expect($this->preferences->getTaskIntegrationConfig())->toBe([])
            ->and($this->preferences->hasTaskIntegration())->toBeFalse();
    });
});

describe('TaskIntegrations Settings Component', function () {
    it('renders the settings page', function () {
        Livewire::test(TaskIntegrations::class)
            ->assertStatus(200)
            ->assertSee('Task Integrations');
    });

    it('shows all providers', function () {
        Livewire::test(TaskIntegrations::class)
            ->assertSee('Notion')
            ->assertSee('Trello')
            ->assertSee('Jira')
            ->assertSee('Asana');
    });

    it('can select a provider', function () {
        Livewire::test(TaskIntegrations::class)
            ->call('selectProvider', 'notion')
            ->assertSet('selectedProvider', 'notion');
    });

    it('shows config fields when provider selected', function () {
        Livewire::test(TaskIntegrations::class)
            ->call('selectProvider', 'notion')
            ->assertSee('Integration Token')
            ->assertSee('Database ID');
    });

    it('can save integration config', function () {
        Livewire::test(TaskIntegrations::class)
            ->call('selectProvider', 'notion')
            ->set('config.api_key', 'test-key')
            ->set('config.database_id', 'test-db')
            ->call('save')
            ->assertDispatched('integration-saved');

        $this->preferences->refresh();

        expect($this->preferences->task_integration_provider)->toBe('notion')
            ->and($this->preferences->task_integration_config['api_key'])->toBe('test-key');
    });

    it('can disconnect integration', function () {
        $this->preferences->update([
            'task_integration_provider' => 'notion',
            'task_integration_config' => ['api_key' => 'key', 'database_id' => 'db'],
        ]);

        Livewire::test(TaskIntegrations::class)
            ->call('disconnect')
            ->assertDispatched('integration-disconnected')
            ->assertSet('selectedProvider', null)
            ->assertSet('isConnected', false);

        $this->preferences->refresh();

        expect($this->preferences->task_integration_provider)->toBeNull();
    });
});

describe('Tasks Index Export', function () {
    it('shows connect button when no integration', function () {
        Livewire::test(Index::class)
            ->assertSee('Connect task manager');
    });

    it('shows connected badge when integration configured', function () {
        $this->preferences->update([
            'task_integration_provider' => 'notion',
            'task_integration_config' => ['api_key' => 'key', 'database_id' => 'db'],
        ]);

        Livewire::test(Index::class)
            ->assertSee('Notion connected');
    });

    it('shows export button on actionable tasks when integration configured', function () {
        $this->preferences->update([
            'task_integration_provider' => 'notion',
            'task_integration_config' => ['api_key' => 'key', 'database_id' => 'db'],
        ]);

        Task::factory()->accepted()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Task',
        ]);

        Livewire::test(Index::class)
            ->assertSee('Export');
    });

    it('shows exported status when task is exported', function () {
        $this->preferences->update([
            'task_integration_provider' => 'notion',
            'task_integration_config' => ['api_key' => 'key', 'database_id' => 'db'],
        ]);

        Task::factory()->accepted()->create([
            'user_id' => $this->user->id,
            'title' => 'Exported Task',
            'metadata' => [
                'external_task' => [
                    'provider' => 'notion',
                    'external_id' => 'page123',
                    'url' => 'https://notion.so/page123',
                    'exported_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        Livewire::test(Index::class)
            ->assertSee('View in Notion');
    });
});

describe('Settings Route', function () {
    it('requires authentication', function () {
        auth()->logout();

        $this->get('/settings/integrations')
            ->assertRedirect('/login');
    });

    it('is accessible when authenticated', function () {
        $this->get('/settings/integrations')
            ->assertStatus(200);
    });
});
