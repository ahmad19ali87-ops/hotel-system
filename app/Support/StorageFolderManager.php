<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StorageFolderManager
{
    /**
     * Core public storage directories required across the entire platform.
     */
    public const CORE_DIRECTORIES = [
        'hotels',
        'hotels/logos',
        'hotels/gallery',
        'destinations',
        'app_banners',
        'avatars',
        'guests',
        'guests/attachments',
        'rooms',
        'offers',
        'messages',
        'messages/attachments',
        'settings',
    ];

    /**
     * Subdirectories to create inside each hotel's storage directory.
     */
    public const HOTEL_SUBDIRECTORIES = [
        'invoices',
        'rooms',
        'gallery',
        'reports',
    ];

    /**
     * Ensure all core storage directories exist with proper permissions (0755).
     */
    public static function ensureCoreDirectories(): void
    {
        $basePublicStorage = public_path('storage');
        self::createPathSafely($basePublicStorage);

        foreach (self::CORE_DIRECTORIES as $dir) {
            self::createPathSafely($basePublicStorage . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir));
        }

        // Ensure temp directory for mPDF invoices exists as well
        self::createPathSafely(storage_path('app/mpdf-temp'));
    }

    /**
     * Ensure all specific directories for a given hotel exist.
     */
    public static function ensureHotelDirectories(?string $slug): void
    {
        if (empty($slug)) {
            return;
        }

        $cleanSlug = Str::slug($slug);
        if (empty($cleanSlug)) {
            return;
        }

        $basePublicStorage = public_path('storage');
        self::createPathSafely($basePublicStorage);

        $hotelRoot = $basePublicStorage . DIRECTORY_SEPARATOR . $cleanSlug;
        self::createPathSafely($hotelRoot);

        foreach (self::HOTEL_SUBDIRECTORIES as $sub) {
            self::createPathSafely($hotelRoot . DIRECTORY_SEPARATOR . $sub);
        }
    }

    /**
     * Safely create a directory without failing if it already exists or if open_basedir / safe mode applies.
     */
    private static function createPathSafely(string $path): void
    {
        try {
            if (!is_dir($path)) {
                File::ensureDirectoryExists($path, 0755, true);
            }
        } catch (\Throwable $e) {
            // Fallback to PHP native mkdir with error suppression
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
        }
    }
}
