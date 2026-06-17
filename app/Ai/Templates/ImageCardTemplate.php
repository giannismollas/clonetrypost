<?php

declare(strict_types=1);

namespace App\Ai\Templates;

use App\Ai\Templates\Concerns\ResolvesContentType;
use App\Services\Image\PostImagePipeline;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ImageCardTemplate implements AiContentTemplate
{
    use ResolvesContentType;

    public function key(): string
    {
        return 'image_card';
    }

    public function name(): string
    {
        return 'posts.ai.templates.image_card.name';
    }

    public function description(): string
    {
        return 'posts.ai.templates.image_card.description';
    }

    public function previewAsset(): string
    {
        return '/images/ai-templates/image-card.png';
    }

    public function needsAccount(): bool
    {
        return false;
    }

    public function supportedFormats(): array
    {
        return [];
    }

    public function generatorFormat(): string
    {
        return 'single';
    }

    public function promptView(): string
    {
        return 'prompts.post_content.generator';
    }

    public function schema(JsonSchema $schema, TemplateContext $context): array
    {
        return [
            'content' => $schema->string()->description('The full post caption text that will be published on the platform.')->required(),
            'image_title' => $schema->string()->description('Short headline (5-12 words) overlaid on the image. The hook — should make a scroller stop. Distinct from content.')->required(),
            'image_body' => $schema->string()->description('1-2 short sentences (max 25 words) overlaid below the image_title. Expands the hook just enough to compel reading the caption.')->required(),
            'image_keywords' => $schema->array()->items($schema->string())->description('2-4 search keywords for Unsplash for the single image.')->required(),
        ];
    }

    public function humanizableFields(): array
    {
        return [
            'content' => 'content',
            'image_title' => 'image_title',
            'image_body' => 'image_body',
        ];
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    public function assemble(array $structured, TemplateContext $context): GeneratedPost
    {
        $contentType = self::resolveContentType($context->format);
        $supportsCaption = $contentType?->supportsCaption() ?? true;

        $rawContent = (string) data_get($structured, 'content', data_get($structured, 'text', ''));

        $media = [];

        if ($context->imageCount > 0 && $context->socialAccount) {
            $media = app(PostImagePipeline::class)->forSingle(
                workspace: $context->workspace,
                account: $context->socialAccount,
                structured: $structured,
                contentType: $contentType,
            );
        }

        $caption = $supportsCaption ? $rawContent : '';

        return new GeneratedPost($caption, $media, $contentType);
    }
}
