<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\Archiver;
use Temant\Archiver\Contract\ArchiverInterface;
use Temant\Archiver\Driver\ZipArchiver;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Exception\UnsupportedFormatException;

final class ArchiverTest extends TestCase
{
    private Archiver $archiver;

    protected function setUp(): void
    {
        $this->archiver = new Archiver();
    }

    #[Test]
    public function it_detects_format_from_path(): void
    {
        $this->assertSame(ArchiveFormat::Zip, $this->archiver->detectFormat('test.zip'));
        $this->assertSame(ArchiveFormat::TarGz, $this->archiver->detectFormat('test.tar.gz'));
        $this->assertSame(ArchiveFormat::TarBz2, $this->archiver->detectFormat('test.tbz2'));
        $this->assertSame(ArchiveFormat::Gz, $this->archiver->detectFormat('test.gz'));
        $this->assertNull($this->archiver->detectFormat('test.txt'));
    }

    #[Test]
    public function it_returns_supported_formats(): void
    {
        $formats = $this->archiver->supportedFormats();
        $this->assertNotEmpty($formats);
        $this->assertContains(ArchiveFormat::Zip, $formats);
        $this->assertContains(ArchiveFormat::Tar, $formats);
    }

    #[Test]
    public function it_returns_driver_for_format(): void
    {
        $driver = $this->archiver->driver(ArchiveFormat::Zip);
        $this->assertInstanceOf(ArchiverInterface::class, $driver);
        $this->assertInstanceOf(ZipArchiver::class, $driver);
    }

    #[Test]
    public function it_throws_for_unsupported_decompress_path(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->archiver->decompress('/nonexistent.xyz', '/tmp/out');
    }

    #[Test]
    public function it_throws_for_unsupported_compress_path(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->archiver->compress('/some/dir', '/output.xyz');
    }

    #[Test]
    public function it_exposes_factory(): void
    {
        $factory = $this->archiver->factory();
        $this->assertInstanceOf(\Temant\Archiver\ArchiverFactory::class, $factory);
    }
}
