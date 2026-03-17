<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\ArchiverFactory;
use Temant\Archiver\Driver\ZipArchiver;
use Temant\Archiver\Driver\TarArchiver;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\UnsupportedFormatException;

final class ArchiverFactoryTest extends TestCase
{
    private ArchiverFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ArchiverFactory();
    }

    #[Test]
    public function it_creates_zip_driver(): void
    {
        $driver = $this->factory->make(ArchiveFormat::Zip);
        $this->assertInstanceOf(ZipArchiver::class, $driver);
    }

    #[Test]
    public function it_creates_tar_driver(): void
    {
        $driver = $this->factory->make(ArchiveFormat::Tar);
        $this->assertInstanceOf(TarArchiver::class, $driver);
    }

    #[Test]
    public function it_creates_driver_from_path(): void
    {
        $driver = $this->factory->makeFromPath('/path/to/archive.zip');
        $this->assertInstanceOf(ZipArchiver::class, $driver);
    }

    #[Test]
    public function it_creates_driver_from_tar_gz_path(): void
    {
        $driver = $this->factory->makeFromPath('/path/to/archive.tar.gz');
        $this->assertSame(ArchiveFormat::TarGz, $driver->format());
    }

    #[Test]
    public function it_throws_for_unknown_path(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->factory->makeFromPath('/path/to/file.unknown');
    }

    #[Test]
    public function it_lists_registered_formats(): void
    {
        $formats = $this->factory->registeredFormats();
        $this->assertContains(ArchiveFormat::Zip, $formats);
        $this->assertContains(ArchiveFormat::Tar, $formats);
        $this->assertContains(ArchiveFormat::TarGz, $formats);
        $this->assertContains(ArchiveFormat::Rar, $formats);
        $this->assertCount(7, $formats);
    }

    #[Test]
    public function it_lists_supported_formats(): void
    {
        $supported = $this->factory->supportedFormats();
        // ZIP and TAR should always be supported
        $this->assertContains(ArchiveFormat::Zip, $supported);
        $this->assertContains(ArchiveFormat::Tar, $supported);
    }

    #[Test]
    public function it_allows_custom_driver_registration(): void
    {
        $customDriver = new ZipArchiver();
        $this->factory->register(ArchiveFormat::Zip, $customDriver);

        $driver = $this->factory->make(ArchiveFormat::Zip);
        $this->assertSame($customDriver, $driver);
    }
}
