<?php

namespace App\Services\Attachment;

use Imagick;
use Illuminate\Support\Facades\Storage;

class AttachmentImageSizeService
{
    /**
     * Generate configured WebP image sizes from an existing local image.
     *
     * @param string $sourcePath
     * @param string $storagePath
     * @param string $baseFilename
     * @return array
     */
    public function generate(string $sourcePath, string $storagePath, string $baseFilename): array
    {
        $image = new Imagick();
        $image->readImage($sourcePath);

        if ($image->getNumberImages() > 1) {
            $image->setIteratorIndex(0);
        }

        $image->setImageColorspace(Imagick::COLORSPACE_RGB);
        $image->stripImage();

        $sizes = [];

        foreach (config('attachments.image.sizes', []) as $key => $sizeConfig) {

            $sizeImage = clone $image;

            $this->resizeImage(
                $sizeImage,
                (int) $sizeConfig['width'],
                isset($sizeConfig['height']) ? (int) $sizeConfig['height'] : null,
                $sizeConfig['mode'] ?? 'contain'
            );

            $filename = $this->makeSizeFilename($baseFilename, $key);
            $path = $storagePath !== '' ? $storagePath . '/' . $filename : $filename;
            $this->writeWebp($sizeImage, $path);

            $sizes[$key] = [
                'path' => $path,
                'width' => $sizeImage->getImageWidth(),
                'height' => $sizeImage->getImageHeight(),
            ];

            $sizeImage->clear();
            $sizeImage->destroy();
        }

        $image->clear();
        $image->destroy();

        return $sizes;
    }

    public function makeSizeFilename(string $baseFilename, string $size): string
    {
        return $baseFilename . '-' . str_replace('_', '-', $size) . '.webp';
    }

    private function resizeImage(
        Imagick $image,
        int $width,
        ?int $height,
        string $mode
    ): void {
        if ($mode === 'cover' && $height !== null) {
            $sourceWidth = $image->getImageWidth();
            $sourceHeight = $image->getImageHeight();
            $ratio = max($width / $sourceWidth, $height / $sourceHeight);

            $resizeWidth = (int) ceil($sourceWidth * $ratio);
            $resizeHeight = (int) ceil($sourceHeight * $ratio);

            $image->resizeImage($resizeWidth, $resizeHeight, Imagick::FILTER_LANCZOS, 1);
            $image->cropImage(
                $width,
                $height,
                max(0, (int) floor(($resizeWidth - $width) / 2)),
                max(0, (int) floor(($resizeHeight - $height) / 2))
            );
            $image->setImagePage(0, 0, 0, 0);

            return;
        }

        if ($height === null) {
            $image->thumbnailImage($width, 0);

            return;
        }

        $image->thumbnailImage($width, $height, true);
    }

    private function writeWebp(Imagick $image, string $path): void
    {
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(85);
        $image->setOption('webp:lossless', 'false');

        Storage::disk('public')->put($path, $image->getImagesBlob());
    }
}
