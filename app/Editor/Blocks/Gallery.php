<?php

namespace App\Editor\Blocks;

use App\Models\Articles\Attachment;
use BumpCore\EditorPhp\Block\Block;

final class Gallery extends Block
{
    public string $type = 'gallery';

    public function rules(): array
    {
        return [
            // Old gallery blocks contain attachment_id; new ones contain images.
            'attachment_id' => ['nullable', 'required_without:images', 'integer'],
            'url'           => ['nullable', 'required_without:images', 'string'],
            'images'        => ['nullable', 'required_without:attachment_id', 'array', 'min:1', 'max:' . config('editor.max_gallery_images')],
            'images.*.attachment_id' => ['required_with:images', 'integer'],
        ];
    }

    public function render(): string
    {
        $images = collect($this->images())
            ->map(function (array $image): array {
                $attachment = ! empty($image['attachment_id'])
                    ? Attachment::find($image['attachment_id'])
                    : null;

                if (! $attachment && empty($image['url'])) {
                    return [];
                }

                return [
                    'src' => $attachment?->getSizeUrl('cover') ?? $image['url'],
                    'fullSrc' => $attachment?->url ?? $image['url'],
                    'srcset' => $attachment?->getSrcset() ?? '',
                    'alt' => $attachment?->alt ?? ($image['alt'] ?? ''),
                    'title' => $attachment?->title ?? ($image['title'] ?? ''),
                ];
            })
            ->filter()
            ->values();

        return $images->isEmpty()
            ? ''
            : view('editor.blocks.gallery', ['images' => $images])->render();
    }

    /** @return list<array<string, mixed>> */
    private function images(): array
    {
        $images = $this->data->get('images');

        if (is_array($images)) {
            return array_values(array_filter($images, 'is_array'));
        }

        // Compatibility with Gallery blocks created before multi-image support.
        return [[
            'attachment_id' => $this->data->get('attachment_id'),
            'url' => $this->data->get('url'),
            'alt' => $this->data->get('alt'),
            'title' => $this->data->get('title'),
        ]];
    }
}
