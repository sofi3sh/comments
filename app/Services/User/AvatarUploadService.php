<?php

namespace App\Services\User;

use Imagick;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarUploadService
{
    private const SIZE = 512;
    private const QUALITY = 85;
    private const DIRECTORY = 'avatars';

    public function upload(UploadedFile $file, ?string $oldPath = null): string
    {
        $path = self::DIRECTORY . '/' . $this->makeFilename($file);

        $image = new Imagick();
        $image->readImage($file->getRealPath());

        if ($image->getNumberImages() > 1) {
            $image->setIteratorIndex(0);
        }

        $image->setImageColorspace(Imagick::COLORSPACE_RGB);
        $image->stripImage();
        $this->resizeToSquare($image);

        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(self::QUALITY);
        Storage::disk('public')->put($path, $image->getImagesBlob());
        $image->clear();
        $image->destroy();

        $this->deleteOld($oldPath, $path);

        return $path;
    }

    private function makeFilename(UploadedFile $file): string
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($name) ?: 'avatar';

        return $slug . '-' . Str::random(8) . '.webp';
    }

    private function resizeToSquare(Imagick $image): void
    {
        $sourceWidth = $image->getImageWidth();
        $sourceHeight = $image->getImageHeight();
        $ratio = max(self::SIZE / $sourceWidth, self::SIZE / $sourceHeight);
        $resizeWidth = (int) ceil($sourceWidth * $ratio);
        $resizeHeight = (int) ceil($sourceHeight * $ratio);

        $image->resizeImage($resizeWidth, $resizeHeight, Imagick::FILTER_LANCZOS, 1);
        $image->cropImage(
            self::SIZE,
            self::SIZE,
            max(0, (int) floor(($resizeWidth - self::SIZE) / 2)),
            max(0, (int) floor(($resizeHeight - self::SIZE) / 2))
        );
        $image->setImagePage(0, 0, 0, 0);
    }

    private function deleteOld(?string $oldPath, string $newPath): void
    {
        if (!$oldPath || $oldPath === $newPath) {
            return;
        }

        if (Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
