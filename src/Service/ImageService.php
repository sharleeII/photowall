<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/**
 * Image processing service.
 *
 * Uses intervention/image v4 with GD driver (no Imagick required).
 * HEIC files are not supported by GD — they are rejected with a clear message.
 *
 * Supported inputs: image/jpeg, image/png, image/webp, image/gif
 */
class ImageService
{
    private const THUMB_QUALITY = 82;

    /**
     * Validate an uploaded file's MIME type and size.
     *
     * @return array{ok:bool, error:string|null}
     */
    public static function validate(string $tmpPath, string $clientMime): array
    {
        $maxMb = (int)Configure::read('Photowall.max_upload_mb');

        // Real MIME check (don't trust client header alone).
        $realMime = mime_content_type($tmpPath) ?: $clientMime;

        if (in_array($realMime, ['image/heic', 'image/heif'], true)) {
            return [
                'ok'    => false,
                'error' => 'Las fotos HEIC de iPhone no son soportadas. En Ajustes > Camara > Formatos, elige "Mas compatible" para subir en JPEG.',
            ];
        }

        $acceptedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($realMime, $acceptedMime, true)) {
            return ['ok' => false, 'error' => "Tipo de archivo no soportado ({$realMime}). Sube una foto JPEG, PNG o WEBP."];
        }

        $sizeMb = filesize($tmpPath) / 1024 / 1024;
        if ($sizeMb > $maxMb) {
            return ['ok' => false, 'error' => "La foto es demasiado grande (max {$maxMb} MB)."];
        }

        return ['ok' => true, 'error' => null];
    }

    /**
     * Generate a thumbnail and save it to $destPath.
     *
     * - Long edge capped at configured px.
     * - EXIF auto-orient applied.
     * - Output is always JPEG for consistent delivery.
     *
     * @throws \RuntimeException on failure
     */
    public static function generateThumb(string $sourcePath, string $destPath): void
    {
        $maxEdge = (int)Configure::read('Photowall.thumb_max_edge_px') ?: 1080;

        $manager = ImageManager::usingDriver(new GdDriver());
        $image = $manager->decodePath($sourcePath);

        // Auto-orient using EXIF data (fixes rotated iPhone JPEGs).
        $image->orient();

        // Scale down if larger than max edge — never upscale.
        $image->scaleDown($maxEdge, $maxEdge);

        // Ensure destination directory exists.
        $dir = dirname($destPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $image->save($destPath, quality: self::THUMB_QUALITY);
    }

    /**
     * Composite a PNG frame (with transparent center) on top of an image file.
     * The frame is scaled to match the image's exact pixel dimensions.
     * Modifies $imagePath in-place (overwrites the file).
     *
     * @throws \RuntimeException on failure
     */
    public static function applyFrame(string $imagePath, string $framePath): void
    {
        $manager = ImageManager::usingDriver(new GdDriver());

        $photo = $manager->decodePath($imagePath);
        $frame = $manager->decodePath($framePath);

        // Stretch frame to photo dimensions so it covers the full area.
        $frame->resize($photo->width(), $photo->height());

        // Composite: frame on top, transparent areas let the photo show through.
        $photo->place($frame);

        $photo->save($imagePath, quality: self::THUMB_QUALITY);
    }

    /**
     * Save original file to $destPath.
     * Converts PNG/WEBP to JPEG for storage consistency.
     * JPEG originals are copied as-is to preserve quality.
     */
    public static function saveOriginal(string $tmpPath, string $destPath): void
    {
        $dir = dirname($destPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $mime = mime_content_type($tmpPath) ?: '';

        if (in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
            $manager = ImageManager::usingDriver(new GdDriver());
            $image = $manager->decodePath($tmpPath);
            $image->orient();
            $image->save($destPath, quality: 90);
        } else {
            // JPEG — copy as-is to preserve quality.
            copy($tmpPath, $destPath);
        }
    }
}
