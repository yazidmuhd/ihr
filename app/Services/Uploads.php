<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * One place for résumé / avatar files, whichever disk holds them.
 *
 * UPLOADS_DISK=public  local development (files under storage/app/public)
 * UPLOADS_DISK=s3      staging / production (private S3-compatible bucket,
 *                      e.g. Railway Storage Bucket, AWS S3, Cloudflare R2)
 *
 * Private disks never expose a permanent link: url() returns a short-lived
 * signed URL, so résumés are only reachable from pages that render them.
 */
class Uploads
{
    public static function diskName(): string
    {
        return config('filesystems.uploads_disk', 'public');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }

    /** Store an uploaded file under $dir and return its path on the uploads disk */
    public static function store(UploadedFile $file, string $dir): string
    {
        $path = $file->store($dir, self::diskName());

        if ($path === false) {
            throw new \RuntimeException('Could not store the uploaded file.');
        }

        return $path;
    }

    /** Link for a stored path (or pass through an absolute URL); signed when the disk is private */
    public static function url(?string $path, int $minutes = 60): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) $path = substr($path, strlen('storage/'));

        return self::isPublic()
            ? self::disk()->url($path)
            : self::disk()->temporaryUrl($path, now()->addMinutes($minutes));
    }

    public static function delete(?string $path): void
    {
        if ($path) self::disk()->delete($path);
    }

    /**
     * Run $callback with a local file path for $path (the résumé parser needs one).
     * Remote files are downloaded to a temporary file that is removed afterwards.
     */
    public static function withLocalCopy(string $path, callable $callback): mixed
    {
        $disk = self::disk();

        if (self::isLocal()) {
            return $callback($disk->path($path));
        }

        $stream = $disk->readStream($path);
        if (!$stream) {
            throw new \RuntimeException("Upload not found: {$path}");
        }

        $tmp = tempnam(sys_get_temp_dir(), 'upload_') . '.' . pathinfo($path, PATHINFO_EXTENSION);
        $out = fopen($tmp, 'wb');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);

        try {
            return $callback($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    private static function isLocal(): bool
    {
        return config('filesystems.disks.' . self::diskName() . '.driver') === 'local';
    }

    private static function isPublic(): bool
    {
        return config('filesystems.disks.' . self::diskName() . '.visibility') === 'public';
    }
}
