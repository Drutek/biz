<?php

namespace App\Services\TaskIntegration;

class ExportResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalId = null,
        public readonly ?string $url = null,
        public readonly ?string $error = null
    ) {}

    public static function success(string $externalId, ?string $url = null): self
    {
        return new self(
            success: true,
            externalId: $externalId,
            url: $url
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toMetadata(string $provider): array
    {
        if (! $this->success) {
            return [];
        }

        return [
            'provider' => $provider,
            'external_id' => $this->externalId,
            'url' => $this->url,
            'exported_at' => now()->toIso8601String(),
        ];
    }
}
