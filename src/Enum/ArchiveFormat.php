<?php

declare(strict_types=1);

namespace Temant\Archiver\Enum;

enum ArchiveFormat: string
{
    case Zip = 'zip';
    case Tar = 'tar';
    case TarGz = 'tar.gz';
    case TarBz2 = 'tar.bz2';
    case Gz = 'gz';
    case Bz2 = 'bz2';
    case Rar = 'rar';

    /**
     * Detect archive format from a file path based on its extension.
     */
    public static function fromPath(string $path): ?self
    {
        $lower = strtolower($path);

        if (str_ends_with($lower, '.tar.gz') || str_ends_with($lower, '.tgz')) {
            return self::TarGz;
        }

        if (str_ends_with($lower, '.tar.bz2') || str_ends_with($lower, '.tbz2')) {
            return self::TarBz2;
        }

        $extension = pathinfo($lower, PATHINFO_EXTENSION);

        return match ($extension) {
            'zip' => self::Zip,
            'tar' => self::Tar,
            'gz', 'gzip' => self::Gz,
            'bz2', 'bzip2' => self::Bz2,
            'rar' => self::Rar,
            default => null,
        };
    }

    /**
     * Get the default file extension for this format.
     */
    public function extension(): string
    {
        return match ($this) {
            self::Zip => 'zip',
            self::Tar => 'tar',
            self::TarGz => 'tar.gz',
            self::TarBz2 => 'tar.bz2',
            self::Gz => 'gz',
            self::Bz2 => 'bz2',
            self::Rar => 'rar',
        };
    }

    /**
     * Whether this format supports compressing directories (multi-file archives).
     */
    public function supportsDirectories(): bool
    {
        return match ($this) {
            self::Zip, self::Tar, self::TarGz, self::TarBz2 => true,
            self::Gz, self::Bz2 => false,
            self::Rar => false,
        };
    }

    /**
     * Whether this format supports password protection.
     */
    public function supportsPassword(): bool
    {
        return match ($this) {
            self::Zip => true,
            default => false,
        };
    }

    /**
     * Whether this format supports compression (not just archiving).
     */
    public function supportsCompression(): bool
    {
        return match ($this) {
            self::Tar => false,
            default => true,
        };
    }

    /**
     * Whether this format only supports decompression (no creation).
     */
    public function isDecompressOnly(): bool
    {
        return $this === self::Rar;
    }

    public function label(): string
    {
        return match ($this) {
            self::Zip => 'ZIP',
            self::Tar => 'TAR',
            self::TarGz => 'TAR.GZ (Gzipped Tar)',
            self::TarBz2 => 'TAR.BZ2 (Bzip2 Tar)',
            self::Gz => 'GZIP',
            self::Bz2 => 'BZIP2',
            self::Rar => 'RAR',
        };
    }
}
