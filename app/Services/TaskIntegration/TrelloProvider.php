<?php

namespace App\Services\TaskIntegration;

use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrelloProvider implements TaskIntegrationProvider
{
    protected const API_URL = 'https://api.trello.com/1';

    public function getKey(): string
    {
        return 'trello';
    }

    public function getName(): string
    {
        return 'Trello';
    }

    public function getDescription(): string
    {
        return 'Create cards on a Trello board';
    }

    public function isImplemented(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function isConfigured(array $config): bool
    {
        return ! empty($config['api_key'])
            && ! empty($config['token'])
            && ! empty($config['list_id']);
    }

    /**
     * @return array<int, array{key: string, label: string, type: string, placeholder?: string, help?: string}>
     */
    public function getConfigFields(): array
    {
        return [
            [
                'key' => 'api_key',
                'label' => 'API Key',
                'type' => 'text',
                'placeholder' => 'Your Trello API key',
                'help' => 'Get your API key from trello.com/power-ups/admin',
            ],
            [
                'key' => 'token',
                'label' => 'Token',
                'type' => 'password',
                'placeholder' => 'Your Trello token',
                'help' => 'Generate a token from the API key page',
            ],
            [
                'key' => 'list_id',
                'label' => 'List ID',
                'type' => 'text',
                'placeholder' => 'The list to add cards to',
                'help' => 'Add .json to any board URL to find list IDs',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return bool|string Returns true on success, or error message string on failure
     */
    public function testConnection(array $config): bool|string
    {
        if (! $this->isConfigured($config)) {
            return 'Missing API key, Token, or List ID';
        }

        try {
            $response = Http::timeout(15)
                ->get(self::API_URL.'/lists/'.$config['list_id'], [
                    'key' => $config['api_key'],
                    'token' => $config['token'],
                ]);

            if ($response->successful()) {
                return true;
            }

            $status = $response->status();

            Log::warning('Trello connection test failed', [
                'status' => $status,
                'response' => $response->body(),
            ]);

            return match ($status) {
                401 => 'Invalid API key or Token',
                404 => 'List not found - check your List ID',
                default => "Trello API error ({$status})",
            };
        } catch (\Exception $e) {
            Log::warning('Trello connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return 'Connection error: '.$e->getMessage();
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function createTask(Task $task, array $config): ExportResult
    {
        if (! $this->isConfigured($config)) {
            return ExportResult::failure('Trello is not configured');
        }

        try {
            $params = [
                'key' => $config['api_key'],
                'token' => $config['token'],
                'idList' => $config['list_id'],
                'name' => $task->title,
                'pos' => 'top',
            ];

            // Add description if available
            if ($task->description) {
                $params['desc'] = $task->description;
            }

            // Add due date if available
            if ($task->due_date) {
                $params['due'] = $task->due_date->toIso8601String();
            }

            $response = Http::timeout(30)
                ->post(self::API_URL.'/cards', $params);

            if (! $response->successful()) {
                $error = $response->json('message') ?? $response->body();
                Log::error('Trello API error', [
                    'status' => $response->status(),
                    'error' => $error,
                    'task_id' => $task->id,
                ]);

                return ExportResult::failure("Trello API error: {$error}");
            }

            $data = $response->json();
            $cardId = $data['id'] ?? null;
            $url = $data['shortUrl'] ?? $data['url'] ?? null;

            if (! $cardId) {
                return ExportResult::failure('No card ID returned from Trello');
            }

            // Add label for priority if high/urgent
            if (in_array($task->priority->value, ['high', 'urgent'])) {
                $this->addPriorityLabel($cardId, $task->priority->value, $config);
            }

            Log::info('Task exported to Trello', [
                'task_id' => $task->id,
                'card_id' => $cardId,
            ]);

            return ExportResult::success($cardId, $url);
        } catch (\Exception $e) {
            Log::error('Failed to export task to Trello', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return ExportResult::failure($e->getMessage());
        }
    }

    /**
     * Attempt to add a priority label to the card.
     *
     * @param  array<string, mixed>  $config
     */
    protected function addPriorityLabel(string $cardId, string $priority, array $config): void
    {
        try {
            $color = $priority === 'urgent' ? 'red' : 'orange';

            Http::timeout(10)->post(self::API_URL.'/cards/'.$cardId.'/labels', [
                'key' => $config['api_key'],
                'token' => $config['token'],
                'color' => $color,
                'name' => ucfirst($priority),
            ]);
        } catch (\Exception $e) {
            // Non-critical, just log and continue
            Log::warning('Failed to add priority label to Trello card', [
                'card_id' => $cardId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
