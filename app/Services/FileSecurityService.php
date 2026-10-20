<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileSecurityService
{
    /**
     * Dangerous file extensions that should NEVER be allowed under any circumstance.
     */
    protected static array $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'phps', 'inc',
        'exe', 'bat', 'cmd', 'sh', 'bash', 'bin', 'msi', 'dll', 'com', 'scr',
        'vbs', 'vbe', 'wsf', 'wsh', 'ps1', 'py', 'pl', 'cgi', 'jar',
        'js', 'mjs', 'jsp', 'asp', 'aspx', 'cer', 'action', 'do',
        'html', 'htm', 'xhtml', 'shtml', 'htaccess', 'htpasswd', 'env',
    ];

    /**
     * Allowed extensions and mime types for general images.
     */
    protected static array $allowedImageTypes = [
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
    ];

    /**
     * Allowed extensions and mime types for messages/chat attachments.
     */
    protected static array $allowedAttachmentTypes = [
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
        'pdf'  => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'doc'  => ['application/msword'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel'],
        'txt'  => ['text/plain'],
    ];

    /**
     * Dangerous substrings to check within uploaded file contents.
     */
    protected static array $dangerousPatterns = [
        '/<\?php/i',
        '/<\?=/i',
        '/eval\s*\(/i',
        '/base64_decode\s*\(/i',
        '/shell_exec\s*\(/i',
        '/system\s*\(/i',
        '/passthru\s*\(/i',
        '/proc_open\s*\(/i',
        '/popen\s*\(/i',
        '/assert\s*\(/i',
        '/<script\b[^>]*>/i',
    ];

    /**
     * Validate and secure an uploaded image.
     * Throws ValidationException or aborts if malicious.
     */
    public static function validateImage(UploadedFile $file, int $maxKilobytes = 5120): string
    {
        return static::validateAndStore($file, static::$allowedImageTypes, 'images', $maxKilobytes);
    }

    /**
     * Validate and secure an attachment (images + documents).
     */
    public static function validateAttachment(UploadedFile $file, int $maxKilobytes = 10240): string
    {
        return static::validateAndStore($file, static::$allowedAttachmentTypes, 'attachments', $maxKilobytes);
    }

    /**
     * Core validation and security scanning algorithm.
     */
    public static function validateAndStore(
        UploadedFile $file,
        array $allowedMap,
        string $directory = 'uploads',
        int $maxKilobytes = 5120
    ): string {
        $ip = request()->ip() ?: '0.0.0.0';
        $originalName = $file->getClientOriginalName();
        $clientExt = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath() ?: $file->getPathname();

        // 1. Check double extensions (e.g. evil.php.png or evil.php.jpg)
        $nameParts = explode('.', strtolower($originalName));
        if (count($nameParts) > 1) {
            foreach ($nameParts as $part) {
                if (in_array($part, static::$dangerousExtensions, true)) {
                    static::triggerAttackDetected($ip, "Malicious double extension detected in file: {$originalName}");
                    throw ValidationException::withMessages([
                        'file' => 'تم رفض الملف لاحتوائه على امتداد محظور وغير آمن.',
                    ]);
                }
            }
        }

        // 2. Strict blacklist check
        if (in_array($clientExt, static::$dangerousExtensions, true)) {
            static::triggerAttackDetected($ip, "Direct blacklist extension upload attempt: .{$clientExt} in {$originalName}");
            throw ValidationException::withMessages([
                'file' => 'نوع الملف المرفوع محظور تماماً لأسباب أمنية.',
            ]);
        }

        // 3. Strict whitelist check
        if (!array_key_exists($clientExt, $allowedMap)) {
            throw ValidationException::withMessages([
                'file' => 'امتداد الملف غير مدعوم. الصيغ المسموحة هي: ' . implode(', ', array_keys($allowedMap)),
            ]);
        }

        // 4. File size check
        $sizeInKb = $file->getSize() / 1024;
        if ($sizeInKb > $maxKilobytes) {
            throw ValidationException::withMessages([
                'file' => "حجم الملف يتجاوز الحد المسموح به وهو " . round($maxKilobytes / 1024, 1) . " ميجابايت.",
            ]);
        }

        // Extract header chunk (first 16KB) safely across environments
        $headerChunk = '';
        if ($file instanceof \Illuminate\Http\Testing\File && is_resource($file->tempFile)) {
            rewind($file->tempFile);
            $headerChunk = (string) stream_get_contents($file->tempFile, 16384);
            rewind($file->tempFile);
        } elseif (!empty($realPath) && @file_exists($realPath) && @is_readable($realPath)) {
            $handle = @fopen($realPath, 'rb');
            if ($handle) {
                $headerChunk = (string) fread($handle, 16384);
                fclose($handle);
            }
        }

        if (empty($headerChunk)) {
            try {
                $headerChunk = (string) substr((string) $file->getContent(), 0, 16384);
            } catch (\Throwable $e) {}
        }

        // 5. Binary MIME inspection via finfo buffer or file
        $realMime = null;
        if (!empty($headerChunk)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $realMime = @finfo_buffer($finfo, $headerChunk);
                finfo_close($finfo);
            }
        }

        if (!$realMime && !empty($realPath) && @file_exists($realPath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $realMime = @finfo_file($finfo, $realPath);
                finfo_close($finfo);
            }
        }

        if (!$realMime) {
            $realMime = $file->getMimeType();
        }

        $validMimesForExt = $allowedMap[$clientExt] ?? [];
        if (!in_array($realMime, $validMimesForExt, true)) {
            static::triggerAttackDetected($ip, "MIME mismatch spoofing attempt. Claimed .{$clientExt} but detected {$realMime}");
            throw ValidationException::withMessages([
                'file' => 'محتوى الملف لا يتطابق مع امتداده المصرح به.',
            ]);
        }

        // 6. Deep Content Inspection (Scan for executable scripts, PHP tags, shells)
        if (!empty($headerChunk)) {
            foreach (static::$dangerousPatterns as $pattern) {
                if (preg_match($pattern, $headerChunk)) {
                    static::triggerAttackDetected($ip, "Malicious executable code signature detected inside file content: {$pattern}");
                    throw ValidationException::withMessages([
                        'file' => 'تم رفض الملف لاكتشاف كود برمجي غير مصرح به داخله.',
                    ]);
                }
            }
        }

        // 7. Generate safe randomized filename (never use client-supplied filename on disk)
        $safeFileName = Str::random(40) . '.' . $clientExt;
        $storedPath = $file->storeAs($directory, $safeFileName, 'public');

        return $storedPath;
    }

    /**
     * Handle security attack detection: record critical log and auto-block IP if threshold exceeded.
     */
    protected static function triggerAttackDetected(string $ip, string $details): void
    {
        SecurityLog::record(
            'malicious_file_upload',
            'critical',
            $details,
            request(),
            auth()->id()
        );

        // Immediate violation recording. 2 attempts = 24-hour block
        BlockedIp::recordViolation($ip, "Malicious file upload attempt: {$details}", 2, 1440, request()->userAgent());
    }
}

