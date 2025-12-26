<?php

namespace App\Services\TaskIntegration;

use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotionProvider implements TaskIntegrationProvider
{
    protected const API_URL = 'https://api.notion.com/v1';

    protected const API_VERSION = '2022-06-28';

    public function getKey(): string
    {
        return 'notion';
    }

    public function getName(): string
    {
        return 'Notion';
    }

    public function getDescription(): string
    {
        return 'Create pages in a Notion database';
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
        return ! empty($config['api_key']) && ! empty($config['database_id']);
    }

    /**
     * @return array<int, array{key: string, label: string, type: string, placeholder?: string, help?: string}>
     */
    public function getConfigFields(): array
    {
        return [
            [
                'key' => 'api_key',
                'label' => 'Integration Token',
                'type' => 'password',
                'placeholder' => 'secret_...',
                'help' => 'Create an internal integration at notion.so/my-integrations',
            ],
            [
                'key' => 'database_id',
                'label' => 'Database ID',
                'type' => 'text',
                'placeholder' => 'abc123...',
                'help' => 'The ID from your database URL (after the workspace name)',
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
            return 'Missing API key or Database ID';
        }

        try {
            $response = Http::withHeaders($this->getHeaders($config['api_key']))
                ->timeout(15)
                ->get(self::API_URL.'/databases/'.$config['database_id']);

            if ($response->successful()) {
                return true;
            }

            $error = $response->json('message') ?? $response->json('code') ?? 'Unknown error';
            $status = $response->status();

            Log::warning('Notion connection test failed', [
                'status' => $status,
                'error' => $error,
                'response' => $response->json(),
            ]);

            return match ($status) {
                401 => 'Invalid API key - check your Integration Token',
                403 => 'Access denied - make sure you shared the database with your integration',
                404 => 'Database not found - check the Database ID',
                default => "Notion API error ({$status}): {$error}",
            };
        } catch (\Exception $e) {
            Log::warning('Notion connection test failed', [
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
            return ExportResult::failure('Notion is not configured');
        }

        try {
            $properties = $this->buildProperties($task);

            $response = Http::withHeaders($this->getHeaders($config['api_key']))
                ->timeout(30)
                ->post(self::API_URL.'/pages', [
                    'parent' => [
                        'database_id' => $config['database_id'],
                    ],
                    'properties' => $properties,
                ]);

            if (! $response->successful()) {
                $error = $response->json('message') ?? 'Unknown error';
                Log::error('Notion API error', [
                    'status' => $response->status(),
                    'error' => $error,
                    'task_id' => $task->id,
                ]);

                return ExportResult::failure("Notion API error: {$error}");
            }

            $data = $response->json();
            $pageId = $data['id'] ?? null;
            $url = $data['url'] ?? null;

            if (! $pageId) {
                return ExportResult::failure('No page ID returned from Notion');
            }

            Log::info('Task exported to Notion', [
                'task_id' => $task->id,
                'page_id' => $pageId,
            ]);

            return ExportResult::success($pageId, $url);
        } catch (\Exception $e) {
            Log::error('Failed to export task to Notion', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return ExportResult::failure($e->getMessage());
        }
    }

    /**
     * Build Notion page properties from task.
     *
     * @return array<string, mixed>
     */
    protected function buildProperties(Task $task): array
    {
        $properties = [
            'Name' => [
                'title' => [
                    [
                        'text' => [
                            'content' => $task->title,
                        ],
                    ],
                ],
            ],
        ];

        // Add description if available
        if ($task->description) {
            $properties['Description'] = [
                'rich_text' => [
                    [
                        'text' => [
                            'content' => substr($task->description, 0, 2000),
                        ],
                    ],
                ],
            ];
        }

        // Add due date if available
        if ($task->due_date) {
            $properties['Due Date'] = [
                'date' => [
                    'start' => $task->due_date->format('Y-m-d'),
                ],
            ];
        }

        // Add status using Notion's native Status type
        $properties['Status'] = [
            'status' => [
                'name' => $this->mapStatus($task->status->value),
            ],
        ];

        // Add priority as a select property (optional - won't fail if not configured)
        $properties['Priority'] = [
            'select' => [
                'name' => ucfirst($task->priority->value),
            ],
        ];

        return $properties;
    }

    /**
     * Map internal status to Notion's default Status property names.
     */
    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'suggested', 'accepted' => 'Not started',
            'in_progress' => 'In progress',
            'completed' => 'Done',
            'rejected', 'cancelled' => 'Not started',
            default => 'Not started',
        };
    }

    /**
     * Get headers for Notion API requests.
     *
     * @return array<string, string>
     */
    protected function getHeaders(string $apiKey): array
    {
        return [
            'Authorization' => 'Bearer '.$apiKey,
            'Notion-Version' => self::API_VERSION,
            'Content-Type' => 'application/json',
        ];
    }
}
