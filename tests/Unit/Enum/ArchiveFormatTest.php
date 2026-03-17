<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Unit\Enum;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\Enum\ArchiveFormat;

final class ArchiveFormatTest extends TestCase
{
    #[Test]
    #[DataProvider('pathDetectionProvider')]
    public function it_detects_format_from_path(string $path, ?ArchiveFormat $expected): void
    {
        $this->assertSame($expected, ArchiveFormat::fromPath($path));
    }

    /**
     * @return iterable<string, array{string, ArchiveFormat|null}>
     */
    public static function pathDetectionProvider(): iterable
    {
        yield 'zip' => ['archive.zip', ArchiveFormat::Zip];
        yield 'ZIP uppercase' => ['ARCHIVE.ZIP', ArchiveFormat::Zip];
        yield 'tar' => ['archive.tar', ArchiveFormat::Tar];
        yield 'tar.gz' => ['archive.tar.gz', ArchiveFormat::TarGz];
        yield 'tgz' => ['archive.tgz', ArchiveFormat::TarGz];
        yield 'tar.bz2' => ['archive.tar.bz2', ArchiveFormat::TarBz2];
        yield 'tbz2' => ['archive.tbz2', ArchiveFormat::TarBz2];
        yield 'gz' => ['file.gz', ArchiveFormat::Gz];
        yield 'gzip' => ['file.gzip', ArchiveFormat::Gz];
        yield 'bz2' => ['file.bz2', ArchiveFormat::Bz2];
        yield 'bzip2' => ['file.bzip2', ArchiveFormat::Bz2];
        yield 'rar' => ['archive.rar', ArchiveFormat::Rar];
        yield 'unknown' => ['file.txt', null];
        yield 'no extension' => ['noextension', null];
        yield 'full path tar.gz' => ['/path/to/archive.tar.gz', ArchiveFormat::TarGz];
        yield 'full path tar.bz2' => ['C:\\path\\to\\archive.tar.bz2', ArchiveFormat::TarBz2];
    }

    #[Test]
    public function it_returns_correct_extensions(): void
    {
        $this->assertSame('zip', ArchiveFormat::Zip->extension());
        $this->assertSame('tar', ArchiveFormat::Tar->extension());
        $this->assertSame('tar.gz', ArchiveFormat::TarGz->extension());
        $this->assertSame('tar.bz2', ArchiveFormat::TarBz2->extension());
        $this->assertSame('gz', ArchiveFormat::Gz->extension());
        $this->assertSame('bz2', ArchiveFormat::Bz2->extension());
        $this->assertSame('rar', ArchiveFormat::Rar->extension());
    }

    #[Test]
    public function it_reports_directory_support_correctly(): void
    {
        $this->assertTrue(ArchiveFormat::Zip->supportsDirectories());
        $this->assertTrue(ArchiveFormat::Tar->supportsDirectories());
        $this->assertTrue(ArchiveFormat::TarGz->supportsDirectories());
        $this->assertTrue(ArchiveFormat::TarBz2->supportsDirectories());
        $this->assertFalse(ArchiveFormat::Gz->supportsDirectories());
        $this->assertFalse(ArchiveFormat::Bz2->supportsDirectories());
        $this->assertFalse(ArchiveFormat::Rar->supportsDirectories());
    }

    #[Test]
    public function it_reports_password_support_correctly(): void
    {
        $this->assertTrue(ArchiveFormat::Zip->supportsPassword());
        $this->assertFalse(ArchiveFormat::Tar->supportsPassword());
        $this->assertFalse(ArchiveFormat::Gz->supportsPassword());
        $this->assertFalse(ArchiveFormat::Rar->supportsPassword());
    }

    #[Test]
    public function it_reports_compression_support_correctly(): void
    {
        $this->assertTrue(ArchiveFormat::Zip->supportsCompression());
        $this->assertFalse(ArchiveFormat::Tar->supportsCompression());
        $this->assertTrue(ArchiveFormat::TarGz->supportsCompression());
        $this->assertTrue(ArchiveFormat::Gz->supportsCompression());
    }

    #[Test]
    public function it_identifies_decompress_only_formats(): void
    {
        $this->assertTrue(ArchiveFormat::Rar->isDecompressOnly());
        $this->assertFalse(ArchiveFormat::Zip->isDecompressOnly());
        $this->assertFalse(ArchiveFormat::Tar->isDecompressOnly());
    }

    #[Test]
    public function it_returns_labels(): void
    {
        $this->assertSame('ZIP', ArchiveFormat::Zip->label());
        $this->assertSame('TAR', ArchiveFormat::Tar->label());
        $this->assertSame('RAR', ArchiveFormat::Rar->label());
        $this->assertNotEmpty(ArchiveFormat::TarGz->label());
    }
}
