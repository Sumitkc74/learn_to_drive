<?php

namespace App\Support;

use App\Models\LearningContentImport;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Safe access to the private learning-content R2 disk.
 *
 * The disk cannot be resolved at all when AWS_LEARNING_CONTENT_BUCKET is
 * unset (the S3 adapter constructor requires a string bucket), and its
 * calls can fail at runtime when the R2 token has no access to the
 * bucket. Either failure used to surface as a 500 on every page that
 * touched a saved review copy. Every touch point in the admin review
 * flow goes through here instead of Storage::disk('learning-content'),
 * so a misconfigured disk degrades to a clear message instead of a
 * 500. There is deliberately no fallback to the local disk: local
 * storage is ephemeral on Render, so silently saving review copies
 * there would lose them on the next deploy.
 */
class LearningContentStorage
{
    public const UNAVAILABLE_MESSAGE = 'Learning-content storage is not configured — set AWS_LEARNING_CONTENT_BUCKET in Render and check the R2 token covers the new bucket.';

    /** The learning-content disk, or null when it cannot be resolved. */
    public static function disk(): ?Filesystem
    {
        try {
            return Storage::disk('learning-content');
        } catch (\Throwable) {
            return null;
        }
    }

    /** The learning-content disk, or a 503 with the fix-it message. */
    public static function diskOrAbort(): Filesystem
    {
        $disk = static::disk();
        abort_unless($disk, 503, static::UNAVAILABLE_MESSAGE);

        return $disk;
    }

    /**
     * Whether the record's saved copy exists on the disk. False when no
     * copy was ever saved, when the stored file is gone, and when the
     * disk cannot be reached at all — never throws.
     */
    public static function hasStoredFile(LearningContentImport $record): bool
    {
        return static::storedExists($record) === true;
    }

    /**
     * Whether the storage itself is unusable for this record: the disk
     * cannot be resolved, or the existence check fails. False when no
     * copy was ever saved (nothing is wrong yet) and when the stored
     * file is simply missing (a stale pre-move row). Used to decide
     * whether to show the "storage not configured" warning.
     */
    public static function unavailable(LearningContentImport $record): bool
    {
        if (! $record->file_path) {
            return false;
        }

        return static::storedExists($record) === null;
    }

    /** The saved copy's bytes, or null when it cannot be read. */
    public static function storedBytes(LearningContentImport $record): ?string
    {
        if (! $record->file_path) {
            return null;
        }
        $disk = static::disk();
        if (! $disk) {
            return null;
        }
        try {
            return $disk->exists($record->file_path) ? $disk->get($record->file_path) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Store bytes on the disk; false when the disk cannot be written. */
    public static function put(string $path, string $contents): bool
    {
        $disk = static::disk();
        if (! $disk) {
            return false;
        }
        try {
            return (bool) $disk->put($path, $contents);
        } catch (\Throwable) {
            return false;
        }
    }

    /** Best-effort delete; never throws (used for cleanup after a failure). */
    public static function delete(string $path): void
    {
        $disk = static::disk();
        if (! $disk) {
            return;
        }
        try {
            $disk->delete($path);
        } catch (\Throwable) {
            // Cleanup only: the original error is reported by the caller.
        }
    }

    /**
     * Existence check with three outcomes: true (file present), false
     * (file absent), null (the check itself could not run — disk
     * unresolvable or the S3 call failed).
     */
    private static function storedExists(LearningContentImport $record): ?bool
    {
        if (! $record->file_path) {
            return false;
        }
        $disk = static::disk();
        if (! $disk) {
            return null;
        }
        try {
            return $disk->exists($record->file_path);
        } catch (\Throwable) {
            return null;
        }
    }
}
