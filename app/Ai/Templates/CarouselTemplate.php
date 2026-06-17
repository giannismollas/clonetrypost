<?php

declare(strict_types=1);

namespace App\Ai\Templates;

use App\Ai\Templates\Concerns\ResolvesContentType;
use App\Enums\PostPlatform\ContentType;
use App\Services\Image\PostImagePipeline;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class CarouselTemplate implements AiContentTemplate
{
    use ResolvesContentType;

    public function key(): string
    {
        return 'carousel';
    }

    public function name(): string
    {
        return 'posts.ai.templates.carousel.name';
    }

    public function description(): string
    {
        return 'posts.ai.templates.carousel.description';
    }

    public function previewAsset(): string
    {
        return '/images/ai-templates/carousel.png';
    }

    public function needsAccount(): bool
    {
        return false;
    }

    public function supportedFormats(): array
    {
        return [ContentType::CAROUSEL_FORMAT];
    }

    public function generatorFormat(): string
    {
        return 'carousel';
    }

    public function promptView(): string
    {
        return 'prompts.post_content.generator';
    }

    public function schema(JsonSchema $schema, TemplateContext $context): array
    {
        $slideCount = $context->imageCount > 0 ? $context->imageCount : 1;

        return [
            'caption' => $schema->string()->description('The Instagram caption for the carousel post.')->required(),
            'slides' => $schema->array()
                ->items($schema->object(fn ($s) => [
                    'role' => $s->string()
                        ->enum(['hook', 'development', 'proof', 'cta'])
                        ->description('The role of this slide in the carousel arc. First slide is `hook` (specific real problem). Last slide is `cta` (one specific next action). Middle slides are `development` (unfold the idea) or `proof` (concrete result, before/after, behind-the-scenes, real learning). For 4+ slides, at least one middle slide must be `proof`.')
                        ->required(),
                    'title' => $s->string()->description('Headline of the slide. Short, impactful.')->required(),
                    'body' => $s->string()->description('Supporting text below the headline. 1-3 sentences.')->required(),
                    'image_keywords' => $s->array()->items($schema->string())->description('2-4 search keywords for Unsplash.')->required(),
                ]))
                ->min($slideCount)
                ->max($slideCount)
                ->description("Exactly {$slideCount} slides for the carousel, in order. First slide must have role `hook`, last slide must have role `cta`.")
                ->required(),
        ];
    }

    public function humanizableFields(): array
    {
        return ['caption' => 'caption'];
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    public function assemble(array $structured, TemplateContext $context): GeneratedPost
    {
        $caption = (string) data_get($structured, 'caption', '');

        $media = [];

        if ($context->socialAccount) {
            $media = app(PostImagePipeline::class)->forCarousel(
                workspace: $context->workspace,
                account: $context->socialAccount,
                structured: $structured,
                contentType: ContentType::InstagramFeed,
            );
        }

        return new GeneratedPost($caption, $media, ContentType::InstagramFeed);
    }
}
