<?php

declare(strict_types=1);

namespace Temant\Archiver\DTO;

/**
 * Represents a single entry (file or directory) within an archive.
 */
final class ArchiveEntry
{
    public function __construct(
        public readonly string $path,
        public readonly int $size,
        public readonly int $compressedSize,
        public readonly bool $isDirectory,
        public readonly int $modifiedTime,
        public readonly ?string $comment = null,
    ) {}

    /**
     * Get the compression ratio as a percentage (0-100).
     * Returns 0 if the file has no size or is a directory.
     */
    public function compressionRatio(): float
    {
        if ($this->isDirectory || $this->size === 0) {
            return 0.0;
        }

        return round((1 - ($this->compressedSize / $this->size)) * 100, 2);
    }

    /**
     * Get the file extension of the entry.
     */
    public function extension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Get the filename without directory path.
     */
    public function filename(): string
    {
        return basename($this->path);
    }
}
