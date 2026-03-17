<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Integration\Driver;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\Driver\GzipArchiver;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Enum\CompressionLevel;
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Tests\TestHelper;

final class GzipArchiverTest extends TestCase
{
    use TestHelper;
    private GzipArchiver $archiver;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->archiver = new GzipArchiver();

        if (!$this->archiver->isSupported()) {
            $this->markTestSkipped('zlib extension not available');
        }

        $this->tempDir = sys_get_temp_dir() . '/archiver_gz_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    #[Test]
    public function it_returns_gz_format(): void
    {
        $this->assertSame(ArchiveFormat::Gz, $this->archiver->format());
    }

    #[Test]
    public function it_compresses_single_file(): void
    {
        $source = $this->tempDir . '/original.txt';
        $dest = $this->tempDir . '/original.txt.gz';

        file_put_contents($source, str_repeat('Compressible data ', 1000));

        $this->archiver->compress($source, $dest);

        $this->assertFileExists($dest);
        $this->assertLessThan(filesize($source), filesize($dest));
    }

    #[Test]
    public function it_decompresses_to_file(): void
    {
        $source = $this->tempDir . '/original.txt';
        $compressed = $this->tempDir . '/original.txt.gz';
        $decompressed = $this->tempDir . '/restored.txt';

        $content = 'Hello GZIP World!';
        file_put_contents($source, $content);

        $this->archiver->compress($source, $compressed);
        $this->archiver->decompress($compressed, $decompressed);

        $this->assertSame($content, file_get_contents($decompressed));
    }

    #[Test]
    public function it_decompresses_to_directory(): void
    {
        $source = $this->tempDir . '/data.txt';
        $compressed = $this->tempDir . '/data.txt.gz';
        $extractDir = $this->tempDir . '/extracted';
        mkdir($extractDir);

        file_put_contents($source, 'Test content');

        $this->archiver->compress($source, $compressed);
        $this->archiver->decompress($compressed, $extractDir);

        $this->assertFileExists($extractDir . '/data.txt');
    }

    #[Test]
    public function it_throws_for_directory_compression(): void
    {
        $this->expectException(CompressionException::class);
        $this->expectExceptionMessage('GZIP only supports single file compression');

        $this->archiver->compress($this->tempDir, $this->tempDir . '/out.gz');
    }

    #[Test]
    public function it_lists_single_entry(): void
    {
        $source = $this->tempDir . '/file.txt';
        $compressed = $this->tempDir . '/file.txt.gz';

        file_put_contents($source, 'Test data for listing');

        $this->archiver->compress($source, $compressed);
        $entries = $this->archiver->list($compressed);

        $this->assertCount(1, $entries);
        $this->assertSame('file.txt', $entries[0]->path);
        $this->assertFalse($entries[0]->isDirectory);
    }

    #[Test]
    public function it_returns_archive_info(): void
    {
        $source = $this->tempDir . '/file.txt';
        $compressed = $this->tempDir . '/file.txt.gz';

        file_put_contents($source, str_repeat('x', 1000));

        $this->archiver->compress($source, $compressed);
        $info = $this->archiver->info($compressed);

        $this->assertSame(ArchiveFormat::Gz, $info->format);
        $this->assertSame(1, $info->fileCount);
        $this->assertSame(0, $info->directoryCount);
    }

    #[Test]
    public function it_verifies_valid_archive(): void
    {
        $source = $this->tempDir . '/file.txt';
        $compressed = $this->tempDir . '/file.txt.gz';

        file_put_contents($source, 'Test data');

        $this->archiver->compress($source, $compressed);

        $this->assertTrue($this->archiver->verify($compressed));
    }

    #[Test]
    public function it_fails_to_verify_nonexistent_file(): void
    {
        $this->assertFalse($this->archiver->verify($this->tempDir . '/nonexistent.gz'));
    }

    #[Test]
    public function it_compresses_with_level(): void
    {
        $source = $this->tempDir . '/level_test.txt';
        file_put_contents($source, str_repeat('Compressible data ', 1000));

        $bestPath = $this->tempDir . '/best.gz';
        $fastestPath = $this->tempDir . '/fastest.gz';

        $this->archiver->compress($source, $bestPath, ['level' => CompressionLevel::Best]);
        $this->archiver->compress($source, $fastestPath, ['level' => CompressionLevel::Fastest]);

        $this->assertFileExists($bestPath);
        $this->assertFileExists($fastestPath);
        // Best compression should produce smaller or equal file
        $this->assertLessThanOrEqual(filesize($fastestPath), filesize($bestPath));
    }

    #[Test]
    public function it_calls_progress_callback_on_compress(): void
    {
        $source = $this->tempDir . '/progress.txt';
        file_put_contents($source, str_repeat('data', 1000));

        $called = false;
        $this->archiver->compress($source, $this->tempDir . '/progress.gz', [
            'progress' => function () use (&$called): void {
                $called = true;
            },
        ]);

        $this->assertTrue($called);
    }

}
