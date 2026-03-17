<?php

declare(strict_types=1);

namespace Temant\Archiver\Driver;

use PharData;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Exception\DecompressionException;

class TarBz2Archiver extends TarGzArchiver
{
    public function format(): ArchiveFormat
    {
        return ArchiveFormat::TarBz2;
    }

    public function isSupported(): bool
    {
        return class_exists(PharData::class) && extension_loaded('bz2');
    }

    public function compress(string $source, string $destination, array $options = []): bool
    {
        $tarPath = $this->deriveTarPath($destination, '/\.(tar\.bz2|tbz2)$/i');

        try {
            if (file_exists($tarPath)) {
                unlink($tarPath);
            }
            if (file_exists($destination)) {
                unlink($destination);
            }

            (new TarArchiver())->compress($source, $tarPath, $options);

            $phar = new PharData($tarPath);
            $phar->compress(\Phar::BZ2);

            if (file_exists($tarPath)) {
                unlink($tarPath);
            }

            $generatedPath = $tarPath . '.bz2';
            if ($generatedPath !== $destination && file_exists($generatedPath)) {
                rename($generatedPath, $destination);
            }

            return true;
        } catch (\Exception $e) {
            if (file_exists($tarPath)) {
                @unlink($tarPath);
            }
            throw new CompressionException("Failed to create TAR.BZ2 archive: {$e->getMessage()}", 0, $e);
        }
    }

    public function decompress(string $archive, string $destination, array $options = []): bool
    {
        try {
            $phar = new PharData($archive);

            $this->ensureDirectory($destination);

            $include = $this->getIncludePatterns($options);
            $exclude = $this->getExcludePatterns($options);
            $progress = $this->getProgressCallback($options);

            if ($include === [] && $exclude === [] && $progress === null) {
                $phar->extractTo($destination, null, $this->getOverwrite($options));
                return true;
            }

            $tarPhar = $phar->decompress();

            $entries = $this->listEntries($tarPhar);
            $total = count($entries);
            $extracted = 0;

            foreach ($entries as $entry) {
                if (!$this->shouldIncludeFile($entry, $include, $exclude)) {
                    continue;
                }

                $tarPhar->extractTo($destination, $entry, $this->getOverwrite($options));
                $extracted++;

                if ($progress !== null) {
                    $progress($entry, $extracted, $total);
                }
            }

            return true;
        } catch (DecompressionException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DecompressionException("Failed to extract TAR.BZ2 archive: {$e->getMessage()}", 0, $e);
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
            format: ArchiveFormat::TarBz2,
            fileCount: $fileCount,
            directoryCount: $dirCount,
            totalSize: $totalSize,
            compressedSize: (int) filesize($archive),
            entries: $entries,
        );
    }
}
