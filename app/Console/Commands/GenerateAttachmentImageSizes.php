<?php

namespace App\Console\Commands;

use App\Models\Articles\Attachment;
use App\Services\Attachment\AttachmentImageSizeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateAttachmentImageSizes extends Command
{
    protected $signature = 'attachments:generate-sizes
        {--id=* : Attachment ID to process, can be passed multiple times}
        {--chunk=100 : Number of attachments to process per chunk}
        {--force : Regenerate sizes even when all configured files exist}
        {--dry-run : Show what would be processed without writing files}';

    protected $description = 'Generate configured responsive image sizes for existing parent attachments';

    /**
     * Generate and synchronize responsive image size attachments.
     *
     * The command processes only parent image attachments. The generated cover
     * file becomes the parent attachment file, while all other configured sizes
     * are stored as child attachment records linked by parent_id.
     *
     * @param AttachmentImageSizeService $imageSizeService
     * @return int
     */
    public function handle(AttachmentImageSizeService $imageSizeService): int
    {
        $processed = 0;
        $generated = 0;
        $skipped = 0;
        $failed = 0;
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = Attachment::query()
            ->whereNull('parent_id')
            ->where('mime_type', 'like', 'image/%')
            ->orderBy('id');

        $ids = array_filter(array_map('intval', (array) $this->option('id')));
        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        $query->chunkById((int) $this->option('chunk'), function ($attachments) use (
            $imageSizeService,
            $dryRun,
            $force,
            &$processed,
            &$generated,
            &$skipped,
            &$failed
        ): void {
            foreach ($attachments as $attachment) {
                $processed++;

                if (!$attachment->path || !Storage::disk('public')->exists($attachment->path)) {
                    $failed++;
                    $this->warn("Attachment {$attachment->id}: source file not found.");

                    continue;
                }

                $attachment->load('sizes');

                if (!$force && $this->hasAllConfiguredSizes($attachment)) {
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $generated++;
                    $this->line("Attachment {$attachment->id}: would generate/sync sizes.");

                    continue;
                }

                $sourcePath = null;

                try {
                    $originalPath = $attachment->path;
                    // Imagick reads from the local filesystem, and the uploads
                    // disk is a bucket — pull the source down first.
                    $sourcePath = $this->downloadSource($attachment->path);
                    $storagePath = $this->storagePath($attachment);
                    $baseFilename = $this->baseFilename($attachment);

                    $sizes = $imageSizeService->generate($sourcePath, $storagePath, $baseFilename);
                    $cover = $sizes['cover'] ?? null;

                    if (!$cover || empty($cover['path']) || !Storage::disk('public')->exists($cover['path'])) {
                        throw new \RuntimeException('Generated cover file not found.');
                    }

                    $this->updateParentCover($attachment, $cover);
                    $this->syncSizeAttachments($attachment, $sizes);
                    $this->deleteOriginal($originalPath, $attachment->path);

                    $generated++;
                    $this->line("Attachment {$attachment->id}: generated/synced sizes.");
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("Attachment {$attachment->id}: {$e->getMessage()}");
                } finally {
                    if ($sourcePath !== null && is_file($sourcePath)) {
                        @unlink($sourcePath);
                    }
                }
            }
        });

        $this->info("Processed: {$processed}");
        $this->info("Generated: {$generated}");
        $this->info("Skipped: {$skipped}");
        $this->info("Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Determine whether a parent attachment already has all configured sizes.
     *
     * This checks the new storage model only: the parent must be the cover
     * size and every non-cover configured size must exist as a child attachment
     * with a physical file on the public disk.
     *
     * @param Attachment $attachment
     * @return bool
     */
    private function hasAllConfiguredSizes(Attachment $attachment): bool
    {
        if ($attachment->size_key !== 'cover' || !Storage::disk('public')->exists($attachment->path)) {
            return false;
        }

        $children = $attachment->sizes->keyBy('size_key');

        foreach (array_keys(config('attachments.image.sizes', [])) as $size) {
            if ($size === 'cover') {
                continue;
            }

            $child = $children->get($size);
            if (!$child || !Storage::disk('public')->exists($child->path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Switch the parent attachment to the generated cover image.
     *
     * @param Attachment $attachment
     * @param array<string, mixed> $cover
     * @return void
     */
    private function updateParentCover(Attachment $attachment, array $cover): void
    {
        $coverPath = $cover['path'];

        $attachment->size_key = 'cover';
        $attachment->path = $coverPath;
        $attachment->filename = basename($coverPath);
        $attachment->mime_type = 'image/webp';
        $attachment->size = Storage::disk('public')->size($coverPath);
        $attachment->metadata = $this->buildMetadata($cover);
        $attachment->save();
    }

    /**
     * Create, update, and remove child attachment rows for generated sizes.
     *
     * Non-cover configured sizes are stored as child attachments. Existing
     * children with size keys no longer present in the configuration are deleted
     * through the model so their physical files are removed as well.
     *
     * @param Attachment $parent
     * @param array<string, array<string, mixed>> $sizes
     * @return void
     */
    private function syncSizeAttachments(Attachment $parent, array $sizes): void
    {
        $configuredSizeKeys = collect(array_keys(config('attachments.image.sizes', [])))
            ->reject(fn (string $size) => $size === 'cover')
            ->values();

        $existing = $parent->sizes()->get()->keyBy('size_key');

        foreach ($configuredSizeKeys as $sizeKey) {
            $size = $sizes[$sizeKey] ?? null;
            $path = $size['path'] ?? null;

            if (!$path || !Storage::disk('public')->exists($path)) {
                continue;
            }

            $child = $existing->get($sizeKey) ?? new Attachment([
                'parent_id' => $parent->id,
                'size_key' => $sizeKey,
            ]);

            $oldPath = $child->exists ? $child->path : null;

            $child->fill([
                'parent_id' => $parent->id,
                'size_key' => $sizeKey,
                'filename' => basename($path),
                'path' => $path,
                'mime_type' => 'image/webp',
                'size' => Storage::disk('public')->size($path),
                'alt' => $parent->alt,
                'title' => $parent->title,
                'caption' => $parent->caption,
                'user_id' => $parent->user_id,
                'metadata' => $this->buildMetadata($size),
            ]);
            $child->save();

            $this->deleteOriginal($oldPath, $path);
        }

        $existing
            ->reject(fn (Attachment $child, ?string $sizeKey) => $configuredSizeKeys->contains($sizeKey))
            ->each(fn (Attachment $child) => $child->delete());
    }

    /**
     * Build normalized metadata for one generated image size.
     *
     * @param array<string, mixed>|null $size
     * @return array<string, int|null>
     */
    private function buildMetadata(?array $size): array
    {
        if (!$size) {
            return [];
        }

        return [
            'width' => $size['width'] ?? null,
            'height' => $size['height'] ?? null,
        ];
    }

    /**
     * Resolve the base filename used for regenerated size files.
     *
     * The command strips known generated-size suffixes to avoid names such as
     * image-cover-card-lg.webp when regenerating from an existing cover file.
     *
     * @param Attachment $attachment
     * @return string
     */
    private function baseFilename(Attachment $attachment): string
    {
        $filename = pathinfo($attachment->filename, PATHINFO_FILENAME);

        return preg_replace('/-(card-sm|card-lg|cover)$/', '', $filename) ?: $filename;
    }

    /**
     * Delete a previous physical file after the attachment points elsewhere.
     *
     * @param string|null $originalPath
     * @param string|null $currentPath
     * @return void
     */
    private function deleteOriginal(?string $originalPath, ?string $currentPath): void
    {
        if (!$originalPath || $originalPath === $currentPath) {
            return;
        }

        if (Storage::disk('public')->exists($originalPath)) {
            Storage::disk('public')->delete($originalPath);
        }
    }

    /**
     * Copy an object from the uploads bucket to a local temporary file.
     *
     * The extension is preserved: Imagick sniffs the format from the magic
     * bytes, but a matching extension keeps its error messages readable.
     *
     * @param string $path
     * @return string Absolute path to the temporary file — the caller deletes it.
     */
    private function downloadSource(string $path): string
    {
        $local = tempnam(sys_get_temp_dir(), 'attachment-');

        if ($local === false) {
            throw new \RuntimeException('Could not create a temporary file for the source image.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension !== '') {
            @unlink($local);
            $local .= '.' . $extension;
        }

        $contents = Storage::disk('public')->get($path);
        if ($contents === null) {
            @unlink($local);

            throw new \RuntimeException("Could not read {$path} from the uploads bucket.");
        }

        file_put_contents($local, $contents);

        return $local;
    }

    /**
     * Get the storage directory where generated files should be written.
     *
     * @param Attachment $attachment
     * @return string
     */
    private function storagePath(Attachment $attachment): string
    {
        $directory = dirname($attachment->path);

        return $directory === '.' ? '' : $directory;
    }
}