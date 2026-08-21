<?php

namespace App\Services\Attachment;

use App\Models\Articles\Attachment;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class AttachmentUploadService
{
    public function __construct(private readonly AttachmentImageSizeService $imageSizeService)
    {
    }

    /**
     * @param UploadedFile $file
     * @param array $data
     * @return Attachment
     * @throws \Exception
     */
    public function upload(UploadedFile $file, array $data = []): Attachment
    {
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, config('attachments.image.mimetypes'))) {
            throw new \Exception('Invalid file type');
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();

        $filename = $this->generateFilename(
            $data['article_tags'] ?? [],
            $originalName
        );

        $folderPath = $this->getFolderPath($filename);
        $storagePath = "attachments/{$folderPath}";

        $authUser = backpack_user();

        if (!$authUser) {
            throw new AuthenticationException();
        }

        // 1. upload file
        [$path, $finalFilename, $finalMime, $sizes] = $this->storeFile(
            $file,
            $storagePath,
            $filename,
            $extension,
            $mimeType
        );

        $cover = $sizes['cover'] ?? null;

        $isPublic = $authUser->hasRole('Admin', 'web') && (bool)($data['is_public'] ?? false);

        // 2. create attachment
        $attachment = Attachment::create([
            'parent_id'  => null,
            'size_key'   => $cover ? 'cover' : null,
            'filename'   => $finalFilename,
            'path'       => $path,
            'mime_type'  => $finalMime,
            'size'       => Storage::disk('public')->size($path),
            'alt'        => $data['alt'] ?? null,
            'title'      => $data['title'] ?? null,
            'caption'    => $data['caption'] ?? null,
            'user_id'    => $authUser->id,
            'is_public'  => $isPublic,
            'metadata'   => $this->buildMetadata($cover),
        ]);

        if ($cover) {
            $this->createSizeAttachments($attachment, $sizes);
        }

        // 3. tags
        $tagIds = $data['tag_ids'] ?? $data['tags'] ?? [];

        if (!empty($tagIds)) {
            $attachment->tags()->sync(array_map('intval', (array)$tagIds));
        }

        return $attachment;
    }


    /**
     * STORE FILE (core logic)
     *
     * @param UploadedFile $file
     * @param string $storagePath
     * @param string $filename
     * @param string $extension
     * @param string $mimeType
     * @return array
     */
    private function storeFile(
        UploadedFile $file,
        string $storagePath,
        string $filename,
        string $extension,
        string $mimeType
    ): array {
        // IMAGE → WEBP
        if (str_starts_with($mimeType, 'image/')) {
            $sizes = $this->imageSizeService->generate($file->getRealPath(), $storagePath, $filename);
            $primarySize = $sizes['cover'] ?? null;

            if (!$primarySize) {
                throw new \RuntimeException('Cover image size was not generated.');
            }

            return [
                $primarySize['path'],
                basename($primarySize['path']),
                'image/webp',
                $sizes,
            ];
        }

        // OTHER FILES
        $filename .= '.' . $extension;

        $path = $file->storeAs(
            $storagePath,
            $filename,
            'public'
        );

        return [
            $path,
            $filename,
            $mimeType,
            [],
        ];
    }


    private function buildMetadata(?array $size): array
    {
        if (!$size) {
            return [];
        }

        return [
            'width'  => $size['width'] ?? null,
            'height' => $size['height'] ?? null,
        ];
    }

    private function createSizeAttachments(Attachment $parent, array $sizes): void
    {
        foreach ($sizes as $sizeKey => $size) {
            if ($sizeKey === 'cover') {
                continue;
            }

            $path = $size['path'] ?? null;
            if (!$path || !Storage::disk('public')->exists($path)) {
                continue;
            }

            Attachment::create([
                'parent_id' => $parent->id,
                'size_key'  => $sizeKey,
                'filename'  => basename($path),
                'path'      => $path,
                'mime_type' => 'image/webp',
                'size'      => Storage::disk('public')->size($path),
                'alt'       => $parent->alt,
                'title'     => $parent->title,
                'caption'   => $parent->caption,
                'user_id'   => $parent->user_id,
                'is_public' => $parent->is_public,
                'metadata'  => $this->buildMetadata($size),
            ]);
        }
    }


    /**
     * FILENAME GENERATION
     *
     * @param array $tags
     * @param string $fallback
     * @return string
     */
    private function generateFilename(array $tags, string $fallback): string
    {
        if (!empty($tags)) {
            $names = array_map(fn($t) => is_array($t) ? ($t['name'] ?? '') : $t, $tags);
            $names = array_filter($names);

            if (!empty($names)) {
                return Str::slug(implode('-', $names)) . '-' . time();
            }
        }

        return Str::slug($fallback) . '-' . time();
    }


    /**
     * FOLDER STRATEGY
     *
     * @param string $filename
     * @return string
     */
    private function getFolderPath(string $filename): string
    {
        $base = substr($filename, 0, 4);

        if (strlen($base) < 4) {
            $base = str_pad($base, 4, '0');
        }

        return substr($base, 0, 2) . '/' . substr($base, 2, 2);
    }
}
