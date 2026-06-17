<?php

declare(strict_types=1);

use App\Ai\Templates\CarouselTemplate;
use App\Ai\Templates\ImageCardTemplate;
use App\Enums\PostPlatform\ContentType;

test('image card template exposes its identity', function () {
    $t = new ImageCardTemplate;
    expect($t->key())->toBe('image_card')
        ->and($t->needsAccount())->toBeFalse()
        ->and($t->generatorFormat())->toBe('single')
        ->and($t->promptView())->toBe('prompts.post_content.generator')
        ->and($t->supportedFormats())->toBe([]);
});

test('carousel template exposes its identity', function () {
    $t = new CarouselTemplate;
    expect($t->key())->toBe('carousel')
        ->and($t->generatorFormat())->toBe('carousel')
        ->and($t->supportedFormats())->toContain(ContentType::CAROUSEL_FORMAT);
});
