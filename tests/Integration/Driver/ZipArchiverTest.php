<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Integration\Driver;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\Driver\ZipArchiver;
use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Enum\CompressionLevel;
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Exception\DecompressionException;
use Temant\Archiver\Tests\TestHelper;

final class ZipArchiverTest extends TestCase
{
    use TestHelper;
    private ZipArchiver $archiver;
    private string $tempDir;
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->archiver = new ZipArchiver();
        $this->tempDir = sys_get_temp_dir() . '/archiver_test_' . uniqid();
        $this->fixtureDir = $this->tempDir . '/fixtures';

        mkdir($this->fixtureDir . '/subdir', 0755, true);
        file_put_contents($this->fixtureDir . '/file1.txt', 'Hello World');
        file_put_contents($this->fixtureDir . '/file2.php', '<?php echo "test";');
        file_put_contents($this->fixtureDir . '/subdir/nested.txt', 'Nested content');
        file_put_contents($this->fixtureDir . '/debug.log', 'debug log data');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    #[Test]
    public function it_reports_supported(): void
    {
        $this->assertTrue($this->archiver->isSupported());
    }

    #[Test]
    public function it_returns_zip_format(): void
    {
        $this->assertSame(ArchiveFormat::Zip, $this->archiver->format());
    }

    #[Test]
    public function it_compresses_directory(): void
    {
        $zipPath = $this->tempDir . '/test.zip';

        $result = $this->archiver->compress($this->fixtureDir, $zipPath);

        $this->assertTrue($result);
        $this->assertFileExists($zipPath);
    }

    #[Test]
    public function it_compresses_single_file(): void
    {
        $zipPath = $this->tempDir . '/single.zip';

        $result = $this->archiver->compress($this->fixtureDir . '/file1.txt', $zipPath);

        $this->assertTrue($result);
        $this->assertFileExists($zipPath);
    }

    #[Test]
    public function it_decompresses_archive(): void
    {
        $zipPath = $this->tempDir . '/test.zip';
        $extractDir = $this->tempDir . '/extracted';

        $this->archiver->compress($this->fixtureDir, $zipPath);
        $result = $this->archiver->decompress($zipPath, $extractDir);

        $this->assertTrue($result);
        $this->assertFileExists($extractDir . '/file1.txt');
        $this->assertFileExists($extractDir . '/file2.php');
        $this->assertFileExists($extractDir . '/subdir/nested.txt');
        $this->assertSame('Hello World', file_get_contents($extractDir . '/file1.txt'));
    }

    #[Test]
    public function it_lists_entries(): void
    {
        $zipPath = $this->tempDir . '/test.zip';
        $this->archiver->compress($this->fixtureDir, $zipPath);

        $entries = $this->archiver->list($zipPath);

        $this->assertNotEmpty($entries);
        $this->assertInstanceOf(ArchiveEntry::class, $entries[0]);

        $paths = array_map(fn(ArchiveEntry $e) => $e->path, $entries);
        $this->assertContains('file1.txt', $paths);
        $this->assertContains('file2.php', $paths);
    }

    #[Test]
    public function it_returns_archive_info(): void
    {
        $zipPath = $this->tempDir . '/test.zip';
        $this->archiver->compress($this->fixtureDir, $zipPath);

        $info = $this->archiver->info($zipPath);

        $this->assertInstanceOf(ArchiveInfo::class, $info);
        $this->assertSame(ArchiveFormat::Zip, $info->format);
        $this->assertGreaterThan(0, $info->fileCount);
        $this->assertGreaterThan(0, $info->totalSize);
    }

    #[Test]
    public function it_verifies_valid_archive(): void
    {
        $zipPath = $this->tempDir . '/test.zip';
        $this->archiver->compress($this->fixtureDir, $zipPath);

        $this->assertTrue($this->archiver->verify($zipPath));
    }

    #[Test]
    public function it_fails_to_verify_invalid_archive(): void
    {
        $fakePath = $this->tempDir . '/fake.zip';
        file_put_contents($fakePath, 'not a zip file');

        $this->assertFalse($this->archiver->verify($fakePath));
    }

    #[Test]
    public function it_compresses_with_password(): void
    {
        $zipPath = $this->tempDir . '/encrypted.zip';

        $result = $this->archiver->compress($this->fixtureDir, $zipPath, [
            'password' => 'secret123',
        ]);

        $this->assertTrue($result);
        $this->assertFileExists($zipPath);
    }

    #[Test]
    public function it_compresses_with_comment(): void
    {
        $zipPath = $this->tempDir . '/commented.zip';

        $this->archiver->compress($this->fixtureDir, $zipPath, [
            'comment' => 'Test archive comment',
        ]);

        $info = $this->archiver->info($zipPath);
        $this->assertSame('Test archive comment', $info->comment);
    }

    #[Test]
    public function it_compresses_with_compression_level(): void
    {
        $zipBest = $this->tempDir . '/best.zip';
        $zipNone = $this->tempDir . '/none.zip';

        $this->archiver->compress($this->fixtureDir, $zipBest, [
            'level' => CompressionLevel::Best,
        ]);
        $this->archiver->compress($this->fixtureDir, $zipNone, [
            'level' => CompressionLevel::None,
        ]);

        $this->assertFileExists($zipBest);
        $this->assertFileExists($zipNone);
    }

    #[Test]
    public function it_compresses_with_exclude_filter(): void
    {
        $zipPath = $this->tempDir . '/filtered.zip';

        $this->archiver->compress($this->fixtureDir, $zipPath, [
            'exclude' => ['*.log'],
        ]);

        $entries = $this->archiver->list($zipPath);
        $paths = array_map(fn(ArchiveEntry $e) => $e->path, $entries);

        $this->assertContains('file1.txt', $paths);
        $this->assertNotContains('debug.log', $paths);
    }

    #[Test]
    public function it_compresses_with_include_filter(): void
    {
        $zipPath = $this->tempDir . '/included.zip';

        $this->archiver->compress($this->fixtureDir, $zipPath, [
            'include' => ['*.txt'],
        ]);

        $entries = $this->archiver->list($zipPath);
        foreach ($entries as $entry) {
            if (!$entry->isDirectory) {
                $this->assertSame('txt', $entry->extension());
            }
        }
    }

    #[Test]
    public function it_calls_progress_callback(): void
    {
        $zipPath = $this->tempDir . '/progress.zip';
        $calls = [];

        $this->archiver->compress($this->fixtureDir, $zipPath, [
            'progress' => function (string $file, int $done, int $total) use (&$calls): void {
                $calls[] = ['file' => $file, 'done' => $done, 'total' => $total];
            },
        ]);

        $this->assertNotEmpty($calls);
        $this->assertSame(1, $calls[0]['done']);
        $this->assertGreaterThan(0, $calls[0]['total']);
    }

    #[Test]
    public function it_decompresses_with_exclude_filter(): void
    {
        $zipPath = $this->tempDir . '/test.zip';
        $extractDir = $this->tempDir . '/filtered_extract';

        $this->archiver->compress($this->fixtureDir, $zipPath);
        $this->archiver->decompress($zipPath, $extractDir, [
            'exclude' => ['*.log'],
        ]);

        $this->assertFileExists($extractDir . '/file1.txt');
        $this->assertFileDoesNotExist($extractDir . '/debug.log');
    }

    #[Test]
    public function it_throws_on_decompress_invalid_archive(): void
    {
        $this->expectException(DecompressionException::class);
        $this->archiver->decompress('/nonexistent/archive.zip', $this->tempDir . '/out');
    }

}
