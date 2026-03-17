<?php

declare(strict_types=1);

namespace Temant\Archiver\Contract;

use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Enum\CompressionLevel;
use Temant\Archiver\Exception\ArchiverException;
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Exception\DecompressionException;

/**
 * Interface for all archive format drivers.
 */
interface ArchiverInterface
{
    /**
     * Get the archive format this driver handles.
     */
    public function format(): ArchiveFormat;

    /**
     * Compress a source (file or directory) into an archive.
     *
     * @param string $source Absolute path to the source file or directory.
     * @param string $destination Absolute path where the archive should be created.
     * @param array<string, mixed> $options Driver-specific options:
     *   - password: (string|null) Password for encryption (ZIP only).
     *   - level: (CompressionLevel) Compression level.
     *   - include: (string[]) Glob patterns to include (empty = include all).
     *   - exclude: (string[]) Glob patterns to exclude.
     *   - comment: (string|null) Archive comment (ZIP only).
     *   - progress: (callable|null) Callback receiving (string $currentFile, int $filesProcessed, int $totalFiles).
     * @return bool Returns true on success.
     * @throws CompressionException On failure.
     */
    public function compress(string $source, string $destination, array $options = []): bool;

    /**
     * Decompress an archive to a destination directory.
     *
     * @param string $archive Absolute path to the archive file.
     * @param string $destination Absolute path to the extraction directory.
     * @param array<string, mixed> $options Driver-specific options:
     *   - password: (string|null) Password for decryption.
     *   - include: (string[]) Glob patterns — only extract matching entries.
     *   - exclude: (string[]) Glob patterns — skip matching entries.
     *   - overwrite: (bool) Whether to overwrite existing files (default: true).
     *   - progress: (callable|null) Progress callback.
     * @return bool Returns true on success.
     * @throws DecompressionException On failure.
     */
    public function decompress(string $archive, string $destination, array $options = []): bool;

    /**
     * List all entries in an archive without extracting.
     *
     * @param array<string, mixed> $options Driver-specific options.
     * @return ArchiveEntry[]
     * @throws ArchiverException If the archive cannot be read.
     */
    public function list(string $archive, array $options = []): array;

    /**
     * Get detailed information about an archive.
     *
     * @throws ArchiverException If the archive cannot be read.
     */
    public function info(string $archive): ArchiveInfo;

    /**
     * Test whether an archive is valid and not corrupted.
     */
    public function verify(string $archive): bool;

    /**
     * Check if the required PHP extensions for this driver are available.
     */
    public function isSupported(): bool;
}
