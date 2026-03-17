<?php

declare(strict_types=1);

namespace Temant\Archiver\Driver;

use RarArchive;
use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Exception\DecompressionException;
use Temant\Archiver\Exception\ArchiverException;

class RarArchiver extends AbstractArchiver
{
    public function format(): ArchiveFormat
    {
        return ArchiveFormat::Rar;
    }

    public function isSupported(): bool
    {
        return extension_loaded('rar');
    }

    public function compress(string $source, string $destination, array $options = []): bool
    {
        throw new CompressionException(
            "RAR compression is not supported. RAR is a proprietary format — only decompression is available."
        );
    }

    public function decompress(string $archive, string $destination, array $options = []): bool
    {
        $password = $this->getPassword($options);
        $include = $this->getIncludePatterns($options);
        $exclude = $this->getExcludePatterns($options);
        $progress = $this->getProgressCallback($options);

        $rar = RarArchive::open($archive, $password ?? '');
        if ($rar === false) {
            throw new DecompressionException("Failed to open RAR archive: {$archive}");
        }

        $this->ensureDirectory($destination);

        $entries = $rar->getEntries();
        if ($entries === false) {
            $rar->close();
            throw new DecompressionException("Failed to read RAR archive entries: {$archive}");
        }

        $total = count($entries);
        $extracted = 0;

        foreach ($entries as $entry) {
            $name = $entry->getName();

            if (!$this->shouldIncludeFile($name, $include, $exclude)) {
                continue;
            }

            if (!$entry->extract($destination)) {
                $rar->close();
                throw new DecompressionException("Failed to extract RAR entry: {$name}");
            }

            $extracted++;

            if ($progress !== null) {
                $progress($name, $extracted, $total);
            }
        }

        $rar->close();
        return true;
    }

    /**
     * @param array<string, mixed> $options
     * @return ArchiveEntry[]
     */
    public function list(string $archive, array $options = []): array
    {
        $password = $this->getPassword($options);

        $rar = RarArchive::open($archive, $password ?? '');
        if ($rar === false) {
            throw new ArchiverException("Failed to open RAR archive: {$archive}");
        }

        $rarEntries = $rar->getEntries();
        if ($rarEntries === false) {
            $rar->close();
            throw new ArchiverException("Failed to read RAR archive entries: {$archive}");
        }

        $entries = [];
        foreach ($rarEntries as $entry) {
            $entries[] = new ArchiveEntry(
                path: $entry->getName(),
                size: (int) $entry->getUnpackedSize(),
                compressedSize: (int) $entry->getPackedSize(),
                isDirectory: $entry->isDirectory(),
                modifiedTime: strtotime($entry->getFileTime()) ?: 0,
            );
        }

        $rar->close();
        return $entries;
    }

    public function info(string $archive): ArchiveInfo
    {
        $rar = RarArchive::open($archive, '');
        if ($rar === false) {
            throw new ArchiverException("Failed to open RAR archive: {$archive}");
        }

        $comment = $rar->getComment();
        $rar->close();

        $entries = $this->list($archive);

        $fileCount = 0;
        $dirCount = 0;
        $totalSize = 0;
        $compressedSize = 0;

        foreach ($entries as $entry) {
            if ($entry->isDirectory) {
                $dirCount++;
            } else {
                $fileCount++;
                $totalSize += $entry->size;
                $compressedSize += $entry->compressedSize;
            }
        }

        return new ArchiveInfo(
            path: $archive,
            format: ArchiveFormat::Rar,
            fileCount: $fileCount,
            directoryCount: $dirCount,
            totalSize: $totalSize,
            compressedSize: $compressedSize,
            entries: $entries,
            comment: $comment ?: null,
        );
    }

    public function verify(string $archive): bool
    {
        $rar = RarArchive::open($archive);
        if ($rar === false) {
            return false;
        }

        $entries = $rar->getEntries();
        if ($entries === false) {
            $rar->close();
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry->isDirectory()) {
                continue;
            }
            $stream = $entry->getStream();
            if ($stream === false) {
                $rar->close();
                return false;
            }
            fclose($stream);
        }

        $rar->close();
        return true;
    }
}
