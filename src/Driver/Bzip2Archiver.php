<?php

declare(strict_types=1);

namespace Temant\Archiver\Driver;

use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Exception\DecompressionException;
use Temant\Archiver\Exception\ArchiverException;

class Bzip2Archiver extends AbstractArchiver
{
    public function format(): ArchiveFormat
    {
        return ArchiveFormat::Bz2;
    }

    public function isSupported(): bool
    {
        return extension_loaded('bz2');
    }

    public function compress(string $source, string $destination, array $options = []): bool
    {
        if (!is_file($source)) {
            throw new CompressionException("BZIP2 only supports single file compression. Use TAR.BZ2 for directories.");
        }

        $level = $this->getCompressionLevel($options);
        $progress = $this->getProgressCallback($options);

        $input = fopen($source, 'rb');
        if ($input === false) {
            throw new CompressionException("Failed to open source file: {$source}");
        }

        $output = bzopen($destination, 'w');
        if ($output === false) {
            fclose($input);
            throw new CompressionException("Failed to create BZIP2 file: {$destination}");
        }

        $totalSize = filesize($source) ?: 0;
        $written = 0;

        while (!feof($input)) {
            $data = fread($input, 8192);
            if ($data === false) {
                break;
            }
            bzwrite($output, $data);
            $written += strlen($data);

            if ($progress !== null) {
                $progress(basename($source), $written, $totalSize);
            }
        }

        fclose($input);
        bzclose($output);

        return true;
    }

    public function decompress(string $archive, string $destination, array $options = []): bool
    {
        $progress = $this->getProgressCallback($options);

        if (is_dir($destination)) {
            $filename = basename($archive);
            $filename = preg_replace('/\.(bz2|bzip2)$/i', '', $filename) ?? $filename;
            $destination = rtrim($destination, "/\\") . '/' . $filename;
        }

        $this->ensureDirectory(dirname($destination));

        $input = bzopen($archive, 'r');
        if ($input === false) {
            throw new DecompressionException("Failed to open BZIP2 file: {$archive}");
        }

        $output = fopen($destination, 'wb');
        if ($output === false) {
            bzclose($input);
            throw new DecompressionException("Failed to create output file: {$destination}");
        }

        $totalSize = filesize($archive) ?: 0;
        $written = 0;

        while (true) {
            $data = bzread($input, 8192);
            if ($data === false || $data === '') {
                break;
            }
            fwrite($output, $data);
            $written += strlen($data);

            if ($progress !== null) {
                $progress(basename($destination), $written, $totalSize);
            }
        }

        bzclose($input);
        fclose($output);

        return true;
    }

    /**
     * @param array<string, mixed> $options
     * @return ArchiveEntry[]
     */
    public function list(string $archive, array $options = []): array
    {
        if (!file_exists($archive)) {
            throw new ArchiverException("BZIP2 file not found: {$archive}");
        }

        $filename = basename($archive);
        $filename = preg_replace('/\.(bz2|bzip2)$/i', '', $filename) ?? $filename;

        return [
            new ArchiveEntry(
                path: $filename,
                size: $this->getDecompressedSize($archive),
                compressedSize: (int) filesize($archive),
                isDirectory: false,
                modifiedTime: filemtime($archive) ?: 0,
            ),
        ];
    }

    public function info(string $archive): ArchiveInfo
    {
        $entries = $this->list($archive);
        $entry = $entries[0];

        return new ArchiveInfo(
            path: $archive,
            format: ArchiveFormat::Bz2,
            fileCount: 1,
            directoryCount: 0,
            totalSize: $entry->size,
            compressedSize: $entry->compressedSize,
            entries: $entries,
        );
    }

    public function verify(string $archive): bool
    {
        $input = bzopen($archive, 'r');
        if ($input === false) {
            return false;
        }

        while (true) {
            $data = bzread($input, 8192);
            if ($data === false) {
                bzclose($input);
                return false;
            }
            if ($data === '') {
                break;
            }
        }

        bzclose($input);
        return true;
    }

    private function getDecompressedSize(string $archive): int
    {
        $input = bzopen($archive, 'r');
        if ($input === false) {
            return 0;
        }

        $size = 0;
        while (true) {
            $data = bzread($input, 8192);
            if ($data === false || $data === '') {
                break;
            }
            $size += strlen($data);
        }

        bzclose($input);
        return $size;
    }
}
