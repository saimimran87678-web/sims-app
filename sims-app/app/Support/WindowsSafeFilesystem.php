<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;

class WindowsSafeFilesystem extends Filesystem
{
    /**
     * Write the contents of a file, replacing it atomically if possible.
     * On Windows, provides exponential retry and safe copy+unlink fallback
     * to eliminate Win32 "Access is denied (code: 5)" rename collisions.
     *
     * @param  string  $path
     * @param  string  $content
     * @param  int|null  $mode
     * @return void
     */
    public function replace($path, $content, $mode = null)
    {
        // Normalise path separators for the host OS
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        clearstatcache(true, $normalizedPath);

        $targetPath = realpath($normalizedPath) ?: $normalizedPath;
        $dir = dirname($targetPath);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        // On non-Windows OS, standard tempnam + rename is completely atomic and safe
        if (PHP_OS_FAMILY !== 'Windows') {
            parent::replace($targetPath, $content, $mode);
            return;
        }

        // ── WINDOWS SAFE ATOMIC REPLACEMENT ────────────────────────────────
        // On Windows NTFS/FAT32, rename() fails with EACCES (code: 5) when:
        // 1. Antivirus / Windows Defender has .tmp open for inspection
        // 2. FastCGI workers or OPcache hold an open read handle on target
        // 3. Transient file lock contention between concurrent worker pools
        $tempPath = @tempnam($dir, basename($targetPath));
        if ($tempPath === false) {
            $tempPath = $dir . DIRECTORY_SEPARATOR . uniqid('w_tmp_', true) . '.tmp';
        }

        if (!is_null($mode)) {
            @chmod($tempPath, $mode);
        }

        file_put_contents($tempPath, $content);

        // Attempt atomic rename with retry loop for Windows file-lock transient states
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                if (@rename($tempPath, $targetPath)) {
                    return;
                }
            } catch (\Throwable $e) {
                // Ignore transient lock and proceed to fallback
            }

            // Fallback: If destination exists, try copy over destination + unlink temp
            try {
                if (@copy($tempPath, $targetPath)) {
                    @unlink($tempPath);
                    return;
                }
            } catch (\Throwable $e) {
                // Lock contention on destination file
            }

            // Wait 25ms before next attempt (gives Windows Defender/handles time to close)
            usleep(25000);
        }

        // Ultimate fallback: direct write with exclusive lock
        try {
            file_put_contents($targetPath, $content, LOCK_EX);
            @unlink($tempPath);
            return;
        } catch (\Throwable $e) {
            // As a last resort, execute parent replace so standard exception is raised
            @unlink($tempPath);
            parent::replace($targetPath, $content, $mode);
        }
    }

    /**
     * Move a file to a new location with Windows retry and copy fallback.
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function move($path, $target)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            for ($attempt = 1; $attempt <= 5; $attempt++) {
                try {
                    if (@rename($path, $target)) {
                        return true;
                    }
                } catch (\Throwable $e) {
                    // Ignore transient lock
                }

                try {
                    if (@copy($path, $target)) {
                        @unlink($path);
                        return true;
                    }
                } catch (\Throwable $e) {
                    // Lock contention
                }

                usleep(25000);
            }
        }

        return parent::move($path, $target);
    }
}
