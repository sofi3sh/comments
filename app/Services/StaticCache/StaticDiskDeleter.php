<?php

namespace App\Services\StaticCache;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Пакетное удаление объектов со статических дисков.
 *
 * Storage::delete(array) внутри — обычный цикл, то есть по одному DeleteObject
 * на ключ. На инвалидации типа статьи это тысячи HTTP-запросов, а
 * static.manual_invalidation.delete_batch_size при этом ничего не группирует.
 * S3 умеет DeleteObjects по 1000 ключей за запрос — им и пользуемся.
 *
 * Под Storage::fake() диск локальный (getClient() у него нет), поэтому там
 * остаётся обычный delete(). Этим путём идут SeoStaticInvalidatorTest и
 * SeoSitemapTest — проверка на AwsS3V3Adapter обязательна, без неё они падают
 * с "Call to undefined method getClient()".
 */
class StaticDiskDeleter
{
    /** Жёсткий лимит DeleteObjects в S3. */
    private const S3_MAX_KEYS_PER_REQUEST = 1000;

    /**
     * @param string $disk имя диска (static-public / static-private)
     * @param list<string> $paths пути относительно корня диска
     * @return list<string> то, что удалить не удалось
     */
    public function delete(string $disk, array $paths): array
    {
        if ($paths === []) {
            return [];
        }

        $filesystem = Storage::disk($disk);

        return $filesystem instanceof AwsS3V3Adapter
            ? $this->deleteViaS3($filesystem, $disk, $paths)
            : $this->deleteViaFilesystem($filesystem, $paths);
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private function deleteViaS3(AwsS3V3Adapter $filesystem, string $disk, array $paths): array
    {
        $client = $filesystem->getClient();
        $bucket = (string) config("filesystems.disks.{$disk}.bucket");

        // getClient() обходит PathPrefixer, поэтому префикс диска (если задан —
        // см. STATIC_PRIVATE_PREFIX в проде) добавляем руками.
        $prefix = trim((string) config("filesystems.disks.{$disk}.root", ''), '/');

        $failed = [];
        $batch = min($this->batchSize(), self::S3_MAX_KEYS_PER_REQUEST);

        foreach (array_chunk($paths, $batch) as $chunk) {
            try {
                $result = $client->deleteObjects([
                    'Bucket' => $bucket,
                    'Delete' => [
                        'Objects' => array_map(
                            fn (string $path): array => ['Key' => $this->key($prefix, $path)],
                            $chunk
                        ),
                        // Отвечает только про неудачные ключи.
                        'Quiet' => true,
                    ],
                ]);
            } catch (Throwable) {
                $failed = array_merge($failed, $chunk);

                continue;
            }

            foreach ($result['Errors'] ?? [] as $error) {
                $failed[] = $this->stripPrefix($prefix, (string) ($error['Key'] ?? ''));
            }
        }

        return array_values($failed);
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private function deleteViaFilesystem(Filesystem $filesystem, array $paths): array
    {
        $failed = [];

        foreach (array_chunk($paths, $this->batchSize()) as $chunk) {
            if (!$filesystem->delete($chunk)) {
                $failed = array_merge($failed, $chunk);
            }
        }

        return $failed;
    }

    private function key(string $prefix, string $path): string
    {
        return $prefix === '' ? $path : $prefix . '/' . $path;
    }

    private function stripPrefix(string $prefix, string $key): string
    {
        if ($prefix === '') {
            return $key;
        }

        return str_starts_with($key, $prefix . '/')
            ? substr($key, strlen($prefix) + 1)
            : $key;
    }

    private function batchSize(): int
    {
        return max(1, (int) config('static.manual_invalidation.delete_batch_size', 1000));
    }
}
