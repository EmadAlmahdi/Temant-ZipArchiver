<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Integration\Driver;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\Driver\TarGzArchiver;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Tests\TestHelper;

final class TarGzArchiverTest extends TestCase
{
    use TestHelper;
    private TarGzArchiver $archiver;
    private string $tempDir;
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->archiver = new TarGzArchiver();

        if (!$this->archiver->isSupported()) {
            $this->markTestSkipped('zlib extension not available');
        }

        $this->tempDir = sys_get_temp_dir() . '/archiver_targz_test_' . uniqid();
        $this->fixtureDir = $this->tempDir . '/fixtures';

        mkdir($this->fixtureDir . '/subdir', 0755, true);
        file_put_contents($this->fixtureDir . '/file1.txt', str_repeat('Hello World ', 100));
        file_put_contents($this->fixtureDir . '/subdir/nested.txt', 'Nested content');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    #[Test]
    public function it_returns_tar_gz_format(): void
    {
        $this->assertSame(ArchiveFormat::TarGz, $this->archiver->format());
    }

    #[Test]
    public function it_compresses_and_decompresses(): void
    {
        $archivePath = $this->tempDir . '/test.tar.gz';
        $extractDir = $this->tempDir . '/extracted';

        $this->archiver->compress($this->fixtureDir, $archivePath);

        $this->assertFileExists($archivePath);
        // tar.gz should be smaller than the original data
        $this->assertGreaterThan(0, filesize($archivePath));

        $this->archiver->decompress($archivePath, $extractDir);

        $this->assertFileExists($extractDir . '/file1.txt');
        $content = file_get_contents($extractDir . '/file1.txt');
        $this->assertIsString($content);
        $this->assertStringContainsString('Hello World', $content);
    }

    #[Test]
    public function it_lists_entries(): void
    {
        $archivePath = $this->tempDir . '/test.tar.gz';
        $this->archiver->compress($this->fixtureDir, $archivePath);

        $entries = $this->archiver->list($archivePath);
        $this->assertNotEmpty($entries);
    }

    #[Test]
    public function it_returns_correct_info_format(): void
    {
        $archivePath = $this->tempDir . '/test.tar.gz';
        $this->archiver->compress($this->fixtureDir, $archivePath);

        $info = $this->archiver->info($archivePath);
        $this->assertSame(ArchiveFormat::TarGz, $info->format);
    }

    #[Test]
    public function it_verifies_archive(): void
    {
        $archivePath = $this->tempDir . '/test.tar.gz';
        $this->archiver->compress($this->fixtureDir, $archivePath);

        $this->assertTrue($this->archiver->verify($archivePath));
    }

}
