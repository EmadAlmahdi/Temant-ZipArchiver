<?php

declare(strict_types=1);

namespace Temant\Archiver;

use Temant\Archiver\Contract\ArchiverInterface;
use Temant\Archiver\Driver\Bzip2Archiver;
use Temant\Archiver\Driver\GzipArchiver;
use Temant\Archiver\Driver\RarArchiver;
use Temant\Archiver\Driver\TarArchiver;
use Temant\Archiver\Driver\TarBz2Archiver;
use Temant\Archiver\Driver\TarGzArchiver;
use Temant\Archiver\Driver\ZipArchiver;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\UnsupportedFormatException;

final class ArchiverFactory
{
    /** @var array<string, ArchiverInterface> */
    private array $drivers = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    /**
     * @throws UnsupportedFormatException
     */
    public function make(ArchiveFormat $format): ArchiverInterface
    {
        $driver = $this->drivers[$format->value] ?? null;

        if ($driver === null) {
            throw new UnsupportedFormatException("No driver registered for format: {$format->label()}");
        }

        if (!$driver->isSupported()) {
            throw new UnsupportedFormatException(
                "Format {$format->label()} is not supported — required PHP extensions are missing."
            );
        }

        return $driver;
    }

    /**
     * @throws UnsupportedFormatException
     */
    public function makeFromPath(string $path): ArchiverInterface
    {
        $format = ArchiveFormat::fromPath($path);

        if ($format === null) {
            throw new UnsupportedFormatException(
                "Cannot detect archive format from path: {$path}"
            );
        }

        return $this->make($format);
    }

    public function register(ArchiveFormat $format, ArchiverInterface $driver): void
    {
        $this->drivers[$format->value] = $driver;
    }

    /**
     * @return ArchiveFormat[]
     */
    public function registeredFormats(): array
    {
        return array_map(
            fn(string $value) => ArchiveFormat::from($value),
            array_keys($this->drivers)
        );
    }

    /**
     * @return ArchiveFormat[]
     */
    public function supportedFormats(): array
    {
        return array_values(array_filter(
            $this->registeredFormats(),
            fn(ArchiveFormat $format) => $this->drivers[$format->value]->isSupported()
        ));
    }

    private function registerDefaults(): void
    {
        $this->drivers = [
            ArchiveFormat::Zip->value => new ZipArchiver(),
            ArchiveFormat::Tar->value => new TarArchiver(),
            ArchiveFormat::TarGz->value => new TarGzArchiver(),
            ArchiveFormat::TarBz2->value => new TarBz2Archiver(),
            ArchiveFormat::Gz->value => new GzipArchiver(),
            ArchiveFormat::Bz2->value => new Bzip2Archiver(),
            ArchiveFormat::Rar->value => new RarArchiver(),
        ];
    }
}
