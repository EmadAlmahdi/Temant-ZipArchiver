<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Integration\Driver;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\Driver\TarArchiver;
use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Tests\TestHelper;

final class TarArchiverTest extends TestCase
{
    use TestHelper;
    private TarArchiver $archiver;
    private string $tempDir;
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->archiver = new TarArchiver();
        $this->tempDir = sys_get_temp_dir() . '/archiver_tar_test_' . uniqid();
        $this->fixtureDir = $this->tempDir . '/fixtures';

        mkdir($this->fixtureDir . '/subdir', 0755, true);
        file_put_contents($this->fixtureDir . '/file1.txt', 'Hello World');
        file_put_contents($this->fixtureDir . '/file2.php', '<?php echo "test";');
        file_put_contents($this->fixtureDir . '/subdir/nested.txt', 'Nested content');
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
    public function it_returns_tar_format(): void
    {
        $this->assertSame(ArchiveFormat::Tar, $this->archiver->format());
    }

    #[Test]
    public function it_compresses_directory(): void
    {
        $tarPath = $this->tempDir . '/test.tar';

        $result = $this->archiver->compress($this->fixtureDir, $tarPath);

        $this->assertTrue($result);
        $this->assertFileExists($tarPath);
    }

    #[Test]
    public function it_compresses_single_file(): void
    {
        $tarPath = $this->tempDir . '/single.tar';

        $result = $this->archiver->compress($this->fixtureDir . '/file1.txt', $tarPath);

        $this->assertTrue($result);
        $this->assertFileExists($tarPath);
    }

    #[Test]
    public function it_decompresses_archive(): void
    {
        $tarPath = $this->tempDir . '/test.tar';
        $extractDir = $this->tempDir . '/extracted';

        $this->archiver->compress($this->fixtureDir, $tarPath);
        $result = $this->archiver->decompress($tarPath, $extractDir);

        $this->assertTrue($result);
        $this->assertFileExists($extractDir . '/file1.txt');
        $this->assertSame('Hello World', file_get_contents($extractDir . '/file1.txt'));
    }

    #[Test]
    public function it_lists_entries(): void
    {
        $tarPath = $this->tempDir . '/test.tar';
        $this->archiver->compress($this->fixtureDir, $tarPath);

        $entries = $this->archiver->list($tarPath);

        $this->assertNotEmpty($entries);
        $this->assertInstanceOf(ArchiveEntry::class, $entries[0]);
    }

    #[Test]
    public function it_returns_archive_info(): void
    {
        $tarPath = $this->tempDir . '/test.tar';
        $this->archiver->compress($this->fixtureDir, $tarPath);

        $info = $this->archiver->info($tarPath);

        $this->assertSame(ArchiveFormat::Tar, $info->format);
        $this->assertGreaterThan(0, $info->fileCount);
    }

    #[Test]
    public function it_verifies_valid_archive(): void
    {
        $tarPath = $this->tempDir . '/test.tar';
        $this->archiver->compress($this->fixtureDir, $tarPath);

        $this->assertTrue($this->archiver->verify($tarPath));
    }

    #[Test]
    public function it_compresses_with_exclude_filter(): void
    {
        $tarPath = $this->tempDir . '/filtered.tar';

        $this->archiver->compress($this->fixtureDir, $tarPath, [
            'exclude' => ['*.php'],
        ]);

        $entries = $this->archiver->list($tarPath);
        foreach ($entries as $entry) {
            $this->assertNotSame('php', $entry->extension());
        }
    }

    #[Test]
    public function it_calls_progress_callback(): void
    {
        $tarPath = $this->tempDir . '/progress.tar';
        $called = false;

        $this->archiver->compress($this->fixtureDir, $tarPath, [
            'progress' => function () use (&$called): void {
                $called = true;
            },
        ]);

        $this->assertTrue($called);
    }

}
