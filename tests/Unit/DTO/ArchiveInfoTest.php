<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;

final class ArchiveInfoTest extends TestCase
{
    private function createSampleInfo(): ArchiveInfo
    {
        return new ArchiveInfo(
            path: '/path/to/archive.zip',
            format: ArchiveFormat::Zip,
            fileCount: 3,
            directoryCount: 1,
            totalSize: 10000,
            compressedSize: 3000,
            entries: [
                new ArchiveEntry('dir/', 0, 0, true, 0),
                new ArchiveEntry('file1.php', 5000, 1500, false, 0),
                new ArchiveEntry('file2.txt', 3000, 1000, false, 0),
                new ArchiveEntry('image.png', 2000, 500, false, 0),
            ],
            comment: 'Test archive',
        );
    }

    #[Test]
    public function it_calculates_compression_ratio(): void
    {
        $info = $this->createSampleInfo();
        $this->assertSame(70.0, $info->compressionRatio());
    }

    #[Test]
    public function it_returns_zero_ratio_for_empty_archive(): void
    {
        $info = new ArchiveInfo(
            path: '/empty.zip',
            format: ArchiveFormat::Zip,
            fileCount: 0,
            directoryCount: 0,
            totalSize: 0,
            compressedSize: 0,
            entries: [],
        );

        $this->assertSame(0.0, $info->compressionRatio());
    }

    #[Test]
    public function it_counts_total_entries(): void
    {
        $info = $this->createSampleInfo();
        $this->assertSame(4, $info->entryCount());
    }

    #[Test]
    public function it_formats_total_size(): void
    {
        $info = $this->createSampleInfo();
        $this->assertSame('9.77 KB', $info->formattedTotalSize());
    }

    #[Test]
    public function it_formats_compressed_size(): void
    {
        $info = $this->createSampleInfo();
        $this->assertSame('2.93 KB', $info->formattedCompressedSize());
    }

    #[Test]
    public function it_formats_zero_bytes(): void
    {
        $info = new ArchiveInfo(
            path: '/empty.zip',
            format: ArchiveFormat::Zip,
            fileCount: 0,
            directoryCount: 0,
            totalSize: 0,
            compressedSize: 0,
            entries: [],
        );

        $this->assertSame('0 B', $info->formattedTotalSize());
    }

    #[Test]
    public function it_filters_entries_by_extension(): void
    {
        $info = $this->createSampleInfo();

        $phpFiles = $info->entriesByExtension('php');
        $this->assertCount(1, $phpFiles);
        $this->assertSame('file1.php', $phpFiles[0]->path);

        $pngFiles = $info->entriesByExtension('.png');
        $this->assertCount(1, $pngFiles);

        $noMatch = $info->entriesByExtension('xml');
        $this->assertCount(0, $noMatch);
    }

    #[Test]
    public function it_searches_entries_by_pattern(): void
    {
        $info = $this->createSampleInfo();

        $results = $info->searchEntries('file*');
        $this->assertCount(2, $results);

        $results = $info->searchEntries('*.php');
        $this->assertCount(1, $results);

        $results = $info->searchEntries('nonexistent*');
        $this->assertCount(0, $results);
    }

    #[Test]
    public function it_stores_comment(): void
    {
        $info = $this->createSampleInfo();
        $this->assertSame('Test archive', $info->comment);
    }

    #[Test]
    public function it_defaults_comment_to_null(): void
    {
        $info = new ArchiveInfo(
            path: '/test.zip',
            format: ArchiveFormat::Zip,
            fileCount: 0,
            directoryCount: 0,
            totalSize: 0,
            compressedSize: 0,
            entries: [],
        );

        $this->assertNull($info->comment);
    }
}
