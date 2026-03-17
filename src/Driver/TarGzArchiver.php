<?php

declare(strict_types=1);

namespace Temant\Archiver\Driver;

use PharData;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Exception\DecompressionException;

class TarGzArchiver extends TarArchiver
{
    public function format(): ArchiveFormat
    {
        return ArchiveFormat::TarGz;
    }

    public function isSupported(): bool
    {
        return parent::isSupported() && extension_loaded('zlib');
    }

    public function compress(string $source, string $destination, array $options = []): bool
    {
        $tarPath = $this->deriveTarPath($destination, '/\.(tar\.gz|tgz)$/i');

        try {
            if (file_exists($tarPath)) {
                unlink($tarPath);
            }
            if (file_exists($destination)) {
                unlink($destination);
            }

            parent::compress($source, $tarPath, $options);

            $phar = new PharData($tarPath);
            $phar->compress(\Phar::GZ);

            if (file_exists($tarPath)) {
                unlink($tarPath);
            }

            $generatedPath = $tarPath . '.gz';
            if ($generatedPath !== $destination && file_exists($generatedPath)) {
                rename($generatedPath, $destination);
            }

            return true;
        } catch (\Exception $e) {
            if (file_exists($tarPath)) {
                @unlink($tarPath);
            }
            throw new CompressionException("Failed to create TAR.GZ archive: {$e->getMessage()}", 0, $e);
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
            throw new DecompressionException("Failed to extract TAR.GZ archive: {$e->getMessage()}", 0, $e);
        }
    }

    public function info(string $archive): ArchiveInfo
    {
        $info = parent::info($archive);

        return new ArchiveInfo(
            path: $info->path,
            format: ArchiveFormat::TarGz,
            fileCount: $info->fileCount,
            directoryCount: $info->directoryCount,
            totalSize: $info->totalSize,
            compressedSize: $info->compressedSize,
            entries: $info->entries,
        );
    }

    protected function deriveTarPath(string $destination, string $pattern): string
    {
        $tarPath = preg_replace($pattern, '.tar', $destination);

        if ($tarPath === null || $tarPath === $destination) {
            return $destination . '.tmp.tar';
        }

        return $tarPath;
    }
}
