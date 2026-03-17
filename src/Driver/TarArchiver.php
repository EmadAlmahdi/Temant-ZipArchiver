<?php

declare(strict_types=1);

namespace Temant\Archiver\Driver;

use PharData;
use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Exception\DecompressionException;
use Temant\Archiver\Exception\ArchiverException;

class TarArchiver extends AbstractArchiver
{
    public function format(): ArchiveFormat
    {
        return ArchiveFormat::Tar;
    }

    public function isSupported(): bool
    {
        return class_exists(PharData::class);
    }

    public function compress(string $source, string $destination, array $options = []): bool
    {
        $include = $this->getIncludePatterns($options);
        $exclude = $this->getExcludePatterns($options);
        $progress = $this->getProgressCallback($options);

        try {
            if (file_exists($destination)) {
                unlink($destination);
            }

            $phar = new PharData($destination);

            if (is_file($source)) {
                $phar->addFile($source, basename($source));
                return true;
            }

            $files = $this->collectFiles($source, $include, $exclude);
            $total = count($files);

            foreach ($files as $index => $filePath) {
                $relativePath = $this->relativePath($filePath, $source);
                $phar->addFile($filePath, $relativePath);

                if ($progress !== null) {
                    $progress($relativePath, $index + 1, $total);
                }
            }

            return true;
        } catch (\Exception $e) {
            throw new CompressionException("Failed to create TAR archive: {$e->getMessage()}", 0, $e);
        }
    }

    public function decompress(string $archive, string $destination, array $options = []): bool
    {
        $include = $this->getIncludePatterns($options);
        $exclude = $this->getExcludePatterns($options);
        $progress = $this->getProgressCallback($options);

        try {
            $phar = new PharData($archive);

            $this->ensureDirectory($destination);

            if ($include === [] && $exclude === [] && $progress === null) {
                $phar->extractTo($destination, null, $this->getOverwrite($options));
                return true;
            }

            $entries = $this->listEntries($phar);
            $total = count($entries);
            $extracted = 0;

            foreach ($entries as $entry) {
                if (!$this->shouldIncludeFile($entry, $include, $exclude)) {
                    continue;
                }

                $phar->extractTo($destination, $entry, $this->getOverwrite($options));
                $extracted++;

                if ($progress !== null) {
                    $progress($entry, $extracted, $total);
                }
            }

            return true;
        } catch (ArchiverException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DecompressionException("Failed to extract TAR archive: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $options
     * @return ArchiveEntry[]
     */
    public function list(string $archive, array $options = []): array
    {
        try {
            $phar = new PharData($archive);
            return $this->buildEntryList($phar);
        } catch (\Exception $e) {
            throw new ArchiverException("Failed to read TAR archive: {$e->getMessage()}", 0, $e);
        }
    }

    public function info(string $archive): ArchiveInfo
    {
        $entries = $this->list($archive);

        $fileCount = 0;
        $dirCount = 0;
        $totalSize = 0;

        foreach ($entries as $entry) {
            if ($entry->isDirectory) {
                $dirCount++;
            } else {
                $fileCount++;
                $totalSize += $entry->size;
            }
        }

        return new ArchiveInfo(
            path: $archive,
            format: $this->format(),
            fileCount: $fileCount,
            directoryCount: $dirCount,
            totalSize: $totalSize,
            compressedSize: (int) filesize($archive),
            entries: $entries,
        );
    }

    public function verify(string $archive): bool
    {
        try {
            $phar = new PharData($archive);
            foreach (new \RecursiveIteratorIterator($phar) as $_) {
            }
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @return string[]
     */
    protected function listEntries(PharData $phar): array
    {
        $names = [];
        $iterator = new \RecursiveIteratorIterator($phar);

        /** @var \PharFileInfo $entry */
        foreach ($iterator as $entry) {
            $names[] = $entry->getFilename();
        }

        return $names;
    }

    /**
     * @return ArchiveEntry[]
     */
    protected function buildEntryList(PharData $phar, string $prefix = ''): array
    {
        $entries = [];
        $iterator = new \RecursiveIteratorIterator(
            $phar,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $entry) {
            /** @var \PharFileInfo $entry */
            $path = $prefix !== '' ? $prefix . '/' . $entry->getFilename() : $entry->getFilename();

            $subPath = $iterator->getSubPathName();
            if ($subPath !== '') {
                $path = str_replace("\\", "/", $subPath);
            }

            $entries[] = new ArchiveEntry(
                path: $path,
                size: $entry->isDir() ? 0 : $entry->getSize(),
                compressedSize: $entry->isDir() ? 0 : (int) $entry->getCompressedSize(),
                isDirectory: $entry->isDir(),
                modifiedTime: $entry->getMTime(),
            );
        }

        return $entries;
    }
}
