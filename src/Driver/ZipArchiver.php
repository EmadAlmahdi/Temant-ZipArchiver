<?php

declare(strict_types=1);

namespace Temant\Archiver\Driver;

use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Exception\DecompressionException;
use Temant\Archiver\Exception\ArchiverException;
use ZipArchive;

class ZipArchiver extends AbstractArchiver
{
    public function format(): ArchiveFormat
    {
        return ArchiveFormat::Zip;
    }

    public function isSupported(): bool
    {
        return extension_loaded('zip');
    }

    public function compress(string $source, string $destination, array $options = []): bool
    {
        $zip = new ZipArchive();
        $password = $this->getPassword($options);
        $level = $this->getCompressionLevel($options);
        $comment = $this->getComment($options);
        $include = $this->getIncludePatterns($options);
        $exclude = $this->getExcludePatterns($options);
        $progress = $this->getProgressCallback($options);

        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new CompressionException("Failed to create ZIP archive: {$destination}");
        }

        if ($password !== null) {
            $zip->setPassword($password);
        }

        if ($comment !== null) {
            $zip->setArchiveComment($comment);
        }

        if (is_file($source)) {
            $zip->addFile($source, basename($source));
            if ($password !== null) {
                $zip->setEncryptionName(basename($source), ZipArchive::EM_AES_256);
            }
            $zip->setCompressionName(basename($source), ZipArchive::CM_DEFLATE, $level->value);
            $zip->close();
            return true;
        }

        $files = $this->collectFiles($source, $include, $exclude);
        $total = count($files);

        foreach ($files as $index => $filePath) {
            $relativePath = $this->relativePath($filePath, $source);

            $zip->addFile($filePath, $relativePath);
            $zip->setCompressionName($relativePath, ZipArchive::CM_DEFLATE, $level->value);

            if ($password !== null) {
                $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
            }

            if ($progress !== null) {
                $progress($relativePath, $index + 1, $total);
            }
        }

        $zip->close();
        return true;
    }

    public function decompress(string $archive, string $destination, array $options = []): bool
    {
        $zip = new ZipArchive();
        $password = $this->getPassword($options);
        $include = $this->getIncludePatterns($options);
        $exclude = $this->getExcludePatterns($options);
        $progress = $this->getProgressCallback($options);

        if ($zip->open($archive) !== true) {
            throw new DecompressionException("Failed to open ZIP archive: {$archive}");
        }

        if ($password !== null) {
            $zip->setPassword($password);
        }

        $this->ensureDirectory($destination);

        if ($include === [] && $exclude === [] && $progress === null) {
            if (!$zip->extractTo($destination)) {
                $zip->close();
                throw new DecompressionException("Failed to extract ZIP archive: {$archive}");
            }
            $zip->close();
            return true;
        }

        $total = $zip->numFiles;
        $extracted = 0;

        for ($i = 0; $i < $total; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }

            if (!$this->shouldIncludeFile($name, $include, $exclude)) {
                continue;
            }

            $zip->extractTo($destination, $name);
            $extracted++;

            if ($progress !== null) {
                $progress($name, $extracted, $total);
            }
        }

        $zip->close();
        return true;
    }

    /**
     * @param array<string, mixed> $options
     * @return ArchiveEntry[]
     */
    public function list(string $archive, array $options = []): array
    {
        $zip = new ZipArchive();

        if ($zip->open($archive, ZipArchive::RDONLY) !== true) {
            throw new ArchiverException("Failed to open ZIP archive: {$archive}");
        }

        $password = $this->getPassword($options);
        if ($password !== null) {
            $zip->setPassword($password);
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                continue;
            }

            $entries[] = new ArchiveEntry(
                path: $stat['name'],
                size: $stat['size'],
                compressedSize: $stat['comp_size'],
                isDirectory: str_ends_with($stat['name'], '/'),
                modifiedTime: $stat['mtime'],
                comment: $zip->getCommentIndex($i) ?: null,
            );
        }

        $zip->close();
        return $entries;
    }

    public function info(string $archive): ArchiveInfo
    {
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

        $zip = new ZipArchive();
        $comment = null;
        if ($zip->open($archive, ZipArchive::RDONLY) === true) {
            $comment = $zip->getArchiveComment() ?: null;
            $zip->close();
        }

        return new ArchiveInfo(
            path: $archive,
            format: ArchiveFormat::Zip,
            fileCount: $fileCount,
            directoryCount: $dirCount,
            totalSize: $totalSize,
            compressedSize: $compressedSize,
            entries: $entries,
            comment: $comment,
        );
    }

    public function verify(string $archive): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($archive, ZipArchive::RDONLY) !== true) {
            return false;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                $zip->close();
                return false;
            }
        }

        $zip->close();
        return true;
    }
}
