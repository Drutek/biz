<?php

namespace App\Services\TaskIntegration;

use App\Models\Task;

class JiraProvider implements TaskIntegrationProvider
{
    public function getKey(): string
    {
        return 'jira';
    }

    public function getName(): string
    {
        return 'Jira';
    }

    public function getDescription(): string
    {
        return 'Create issues in a Jira project';
    }

    public function isImplemented(): bool
    {
        return false;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function isConfigured(array $config): bool
    {
        return ! empty($config['domain'])
            && ! empty($config['email'])
            && ! empty($config['api_token'])
            && ! empty($config['project_key']);
    }

    /**
     * @return array<int, array{key: string, label: string, type: string, placeholder?: string, help?: string}>
     */
    public function getConfigFields(): array
    {
        return [
            [
                'key' => 'domain',
                'label' => 'Jira Domain',
                'type' => 'text',
                'placeholder' => 'your-company.atlassian.net',
                'help' => 'Your Atlassian domain (without https://)',
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'placeholder' => 'your@email.com',
                'help' => 'Your Atlassian account email',
            ],
            [
                'key' => 'api_token',
                'label' => 'API Token',
                'type' => 'password',
                'placeholder' => 'Your API token',
                'help' => 'Create a token at id.atlassian.com/manage/api-tokens',
            ],
            [
                'key' => 'project_key',
                'label' => 'Project Key',
                'type' => 'text',
                'placeholder' => 'PROJ',
                'help' => 'The project key (e.g., PROJ from PROJ-123)',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function testConnection(array $config): bool|string
    {
        return 'Jira integration is coming soon';
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function createTask(Task $task, array $config): ExportResult
    {
        return ExportResult::failure('Jira integration is coming soon');
    }
}
