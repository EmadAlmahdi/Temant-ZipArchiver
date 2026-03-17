<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\DTO\ArchiveEntry;

final class ArchiveEntryTest extends TestCase
{
    #[Test]
    public function it_stores_properties(): void
    {
        $entry = new ArchiveEntry(
            path: 'src/file.php',
            size: 1000,
            compressedSize: 400,
            isDirectory: false,
            modifiedTime: 1700000000,
            comment: 'test comment',
        );

        $this->assertSame('src/file.php', $entry->path);
        $this->assertSame(1000, $entry->size);
        $this->assertSame(400, $entry->compressedSize);
        $this->assertFalse($entry->isDirectory);
        $this->assertSame(1700000000, $entry->modifiedTime);
        $this->assertSame('test comment', $entry->comment);
    }

    #[Test]
    public function it_calculates_compression_ratio(): void
    {
        $entry = new ArchiveEntry(
            path: 'file.txt',
            size: 1000,
            compressedSize: 300,
            isDirectory: false,
            modifiedTime: 0,
        );

        $this->assertSame(70.0, $entry->compressionRatio());
    }

    #[Test]
    public function it_returns_zero_ratio_for_directory(): void
    {
        $entry = new ArchiveEntry(
            path: 'dir/',
            size: 0,
            compressedSize: 0,
            isDirectory: true,
            modifiedTime: 0,
        );

        $this->assertSame(0.0, $entry->compressionRatio());
    }

    #[Test]
    public function it_returns_zero_ratio_for_zero_size(): void
    {
        $entry = new ArchiveEntry(
            path: 'empty.txt',
            size: 0,
            compressedSize: 0,
            isDirectory: false,
            modifiedTime: 0,
        );

        $this->assertSame(0.0, $entry->compressionRatio());
    }

    #[Test]
    public function it_extracts_file_extension(): void
    {
        $entry = new ArchiveEntry(
            path: 'src/Controller/HomeController.php',
            size: 100,
            compressedSize: 50,
            isDirectory: false,
            modifiedTime: 0,
        );

        $this->assertSame('php', $entry->extension());
    }

    #[Test]
    public function it_extracts_filename(): void
    {
        $entry = new ArchiveEntry(
            path: 'src/Controller/HomeController.php',
            size: 100,
            compressedSize: 50,
            isDirectory: false,
            modifiedTime: 0,
        );

        $this->assertSame('HomeController.php', $entry->filename());
    }

    #[Test]
    public function it_defaults_comment_to_null(): void
    {
        $entry = new ArchiveEntry(
            path: 'file.txt',
            size: 100,
            compressedSize: 50,
            isDirectory: false,
            modifiedTime: 0,
        );

        $this->assertNull($entry->comment);
    }
}
