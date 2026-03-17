<?php

declare(strict_types=1);

namespace Temant\Archiver;

use Temant\Archiver\Contract\ArchiverInterface;
use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\UnsupportedFormatException;

final class Archiver
{
    private ArchiverFactory $factory;

    public function __construct(?ArchiverFactory $factory = null)
    {
        $this->factory = $factory ?? new ArchiverFactory();
    }

    /**
     * @param array<string, mixed> $options
     * @throws UnsupportedFormatException
     */
    public function compress(string $source, string $destination, array $options = []): bool
    {
        return $this->factory->makeFromPath($destination)->compress($source, $destination, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @throws UnsupportedFormatException
     */
    public function decompress(string $archive, string $destination, array $options = []): bool
    {
        return $this->factory->makeFromPath($archive)->decompress($archive, $destination, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return ArchiveEntry[]
     * @throws UnsupportedFormatException
     */
    public function list(string $archive, array $options = []): array
    {
        return $this->factory->makeFromPath($archive)->list($archive, $options);
    }

    /**
     * @throws UnsupportedFormatException
     */
    public function info(string $archive): ArchiveInfo
    {
        return $this->factory->makeFromPath($archive)->info($archive);
    }

    /**
     * @throws UnsupportedFormatException
     */
    public function verify(string $archive): bool
    {
        return $this->factory->makeFromPath($archive)->verify($archive);
    }

    public function detectFormat(string $path): ?ArchiveFormat
    {
        return ArchiveFormat::fromPath($path);
    }

    /**
     * @return ArchiveFormat[]
     */
    public function supportedFormats(): array
    {
        return $this->factory->supportedFormats();
    }

    /**
     * @throws UnsupportedFormatException
     */
    public function driver(ArchiveFormat $format): ArchiverInterface
    {
        return $this->factory->make($format);
    }

    public function factory(): ArchiverFactory
    {
        return $this->factory;
    }
}
