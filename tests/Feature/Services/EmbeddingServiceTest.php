<?php

use App\Services\Embedding\EmbeddingService;

it('converts embedding array to postgres vector string', function () {
    $service = new EmbeddingService;

    $embedding = [0.1, 0.2, 0.3, -0.5];
    $result = $service->toVectorString($embedding);

    expect($result)->toBe('[0.1,0.2,0.3,-0.5]');
});

it('returns correct dimensions', function () {
    $service = new EmbeddingService;

    expect($service->getDimensions())->toBe(1536);
});

it('returns correct model name', function () {
    $service = new EmbeddingService;

    expect($service->getModel())->toBe('text-embedding-3-small');
});

it('returns empty array when no texts provided for batch embedding', function () {
    $service = new EmbeddingService;

    $result = $service->embedBatch([]);

    expect($result)->toBeArray()->toBeEmpty();
});

it('returns null when no api key is configured', function () {
    $service = new EmbeddingService;

    $result = $service->embed('test text');

    expect($result)->toBeNull();
});
