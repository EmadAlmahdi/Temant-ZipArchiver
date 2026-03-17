<?php

declare(strict_types=1);

namespace Temant\Archiver\Driver;

use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Exception\DecompressionException;
use Temant\Archiver\Exception\ArchiverException;

class GzipArchiver extends AbstractArchiver
{
    public function format(): ArchiveFormat
    {
        return ArchiveFormat::Gz;
    }

    public function isSupported(): bool
    {
        return extension_loaded('zlib');
    }

    public function compress(string $source, string $destination, array $options = []): bool
    {
        if (!is_file($source)) {
            throw new CompressionException("GZIP only supports single file compression. Use TAR.GZ for directories.");
        }

        $level = $this->getCompressionLevel($options);
        $progress = $this->getProgressCallback($options);

        $input = fopen($source, 'rb');
        if ($input === false) {
            throw new CompressionException("Failed to open source file: {$source}");
        }

        $output = gzopen($destination, 'wb' . $level->value);
        if ($output === false) {
            fclose($input);
            throw new CompressionException("Failed to create GZIP file: {$destination}");
        }

        $totalSize = filesize($source) ?: 0;
        $written = 0;
        $chunkSize = 8192;

        while (!feof($input)) {
            $data = fread($input, $chunkSize);
            if ($data === false) {
                break;
            }
            gzwrite($output, $data);
            $written += strlen($data);

            if ($progress !== null) {
                $progress(basename($source), $written, $totalSize);
            }
        }

        fclose($input);
        gzclose($output);

        return true;
    }

    public function decompress(string $archive, string $destination, array $options = []): bool
    {
        $progress = $this->getProgressCallback($options);

        if (is_dir($destination)) {
            $filename = basename($archive);
            $filename = preg_replace('/\.(gz|gzip)$/i', '', $filename) ?? $filename;
            $destination = rtrim($destination, "/\\") . '/' . $filename;
        }

        $this->ensureDirectory(dirname($destination));

        $input = gzopen($archive, 'rb');
        if ($input === false) {
            throw new DecompressionException("Failed to open GZIP file: {$archive}");
        }

        $output = fopen($destination, 'wb');
        if ($output === false) {
            gzclose($input);
            throw new DecompressionException("Failed to create output file: {$destination}");
        }

        $totalSize = filesize($archive) ?: 0;
        $written = 0;

        while (!gzeof($input)) {
            $data = gzread($input, 8192);
            if ($data === false || $data === '') {
                break;
            }
            fwrite($output, $data);
            $written += strlen($data);

            if ($progress !== null) {
                $progress(basename($destination), $written, $totalSize);
            }
        }

        gzclose($input);
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
            throw new ArchiverException("GZIP file not found: {$archive}");
        }

        $filename = basename($archive);
        $filename = preg_replace('/\.(gz|gzip)$/i', '', $filename) ?? $filename;

        $originalSize = $this->readOriginalSize($archive);

        return [
            new ArchiveEntry(
                path: $filename,
                size: $originalSize,
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
            format: ArchiveFormat::Gz,
            fileCount: 1,
            directoryCount: 0,
            totalSize: $entry->size,
            compressedSize: $entry->compressedSize,
            entries: $entries,
        );
    }

    public function verify(string $archive): bool
    {
        $input = gzopen($archive, 'rb');
        if ($input === false) {
            return false;
        }

        while (!gzeof($input)) {
            $data = gzread($input, 8192);
            if ($data === false) {
                gzclose($input);
                return false;
            }
        }

        gzclose($input);
        return true;
    }

    private function readOriginalSize(string $archive): int
    {
        $handle = fopen($archive, 'rb');
        if ($handle === false) {
            return 0;
        }

        fseek($handle, -4, SEEK_END);
        $data = fread($handle, 4);
        fclose($handle);

        if ($data === false || strlen($data) !== 4) {
            return 0;
        }

        $unpacked = unpack('Vsize', $data);
        if ($unpacked === false || !isset($unpacked['size'])) {
            return 0;
        }

        $size = $unpacked['size'];

        return is_int($size) ? $size : 0;
    }
}
