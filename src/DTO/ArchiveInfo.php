<?php

declare(strict_types=1);

namespace Temant\Archiver\DTO;

use Temant\Archiver\Enum\ArchiveFormat;

/**
 * Contains metadata about an archive file.
 */
final class ArchiveInfo
{
    /**
     * @param ArchiveEntry[] $entries
     */
    public function __construct(
        public readonly string $path,
        public readonly ArchiveFormat $format,
        public readonly int $fileCount,
        public readonly int $directoryCount,
        public readonly int $totalSize,
        public readonly int $compressedSize,
        public readonly array $entries,
        public readonly ?string $comment = null,
    ) {}

    /**
     * Get the overall compression ratio as a percentage.
     */
    public function compressionRatio(): float
    {
        if ($this->totalSize === 0) {
            return 0.0;
        }

        return round((1 - ($this->compressedSize / $this->totalSize)) * 100, 2);
    }

    /**
     * Get total number of entries (files + directories).
     */
    public function entryCount(): int
    {
        return $this->fileCount + $this->directoryCount;
    }

    /**
     * Get human-readable total size.
     */
    public function formattedTotalSize(): string
    {
        return self::formatBytes($this->totalSize);
    }

    /**
     * Get human-readable compressed size.
     */
    public function formattedCompressedSize(): string
    {
        return self::formatBytes($this->compressedSize);
    }

    /**
     * Filter entries by extension.
     *
     * @return ArchiveEntry[]
     */
    public function entriesByExtension(string $extension): array
    {
        $ext = ltrim(strtolower($extension), '.');

        return array_values(array_filter(
            $this->entries,
            fn(ArchiveEntry $entry) => strtolower($entry->extension()) === $ext
        ));
    }

    /**
     * Search entries by path pattern (glob-style).
     *
     * @return ArchiveEntry[]
     */
    public function searchEntries(string $pattern): array
    {
        return array_values(array_filter(
            $this->entries,
            fn(ArchiveEntry $entry) => fnmatch($pattern, $entry->path)
        ));
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
