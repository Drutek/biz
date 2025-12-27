<?php

namespace App\Services\LLM\Tools;

interface Tool
{
    /**
     * Get the tool name (used as identifier).
     */
    public function name(): string;

    /**
     * Get the tool description for the LLM.
     */
    public function description(): string;

    /**
     * Get the input schema for the tool.
     *
     * @return array<string, mixed>
     */
    public function inputSchema(): array;

    /**
     * Execute the tool with the given input.
     *
     * @param  array<string, mixed>  $input
     */
    public function execute(array $input): string;

    /**
     * Convert to Claude API format.
     *
     * @return array<string, mixed>
     */
    public function toClaudeFormat(): array;
}
