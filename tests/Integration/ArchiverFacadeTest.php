<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\Archiver;
use Temant\Archiver\DTO\ArchiveEntry;
use Temant\Archiver\DTO\ArchiveInfo;
use Temant\Archiver\Enum\ArchiveFormat;
use Temant\Archiver\Tests\TestHelper;

final class ArchiverFacadeTest extends TestCase
{
    use TestHelper;
    private Archiver $archiver;
    private string $tempDir;
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->archiver = new Archiver();
        $this->tempDir = sys_get_temp_dir() . '/archiver_facade_test_' . uniqid();
        $this->fixtureDir = $this->tempDir . '/fixtures';

        mkdir($this->fixtureDir . '/subdir', 0755, true);
        file_put_contents($this->fixtureDir . '/readme.txt', 'Read me please');
        file_put_contents($this->fixtureDir . '/code.php', '<?php phpinfo();');
        file_put_contents($this->fixtureDir . '/subdir/data.json', '{"key": "value"}');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    #[Test]
    public function it_compresses_and_decompresses_zip(): void
    {
        $zipPath = $this->tempDir . '/facade.zip';
        $extractDir = $this->tempDir . '/zip_extract';

        $this->archiver->compress($this->fixtureDir, $zipPath);
        $this->assertFileExists($zipPath);

        $this->archiver->decompress($zipPath, $extractDir);
        $this->assertFileExists($extractDir . '/readme.txt');
        $this->assertSame('Read me please', file_get_contents($extractDir . '/readme.txt'));
    }

    #[Test]
    public function it_compresses_and_decompresses_tar(): void
    {
        $tarPath = $this->tempDir . '/facade.tar';
        $extractDir = $this->tempDir . '/tar_extract';

        $this->archiver->compress($this->fixtureDir, $tarPath);
        $this->assertFileExists($tarPath);

        $this->archiver->decompress($tarPath, $extractDir);
        $this->assertFileExists($extractDir . '/readme.txt');
    }

    #[Test]
    public function it_compresses_and_decompresses_tar_gz(): void
    {
        $archivePath = $this->tempDir . '/facade.tar.gz';
        $extractDir = $this->tempDir . '/targz_extract';

        $this->archiver->compress($this->fixtureDir, $archivePath);
        $this->assertFileExists($archivePath);

        $this->archiver->decompress($archivePath, $extractDir);
        $this->assertFileExists($extractDir . '/readme.txt');
    }

    #[Test]
    public function it_lists_archive_entries(): void
    {
        $zipPath = $this->tempDir . '/list.zip';
        $this->archiver->compress($this->fixtureDir, $zipPath);

        $entries = $this->archiver->list($zipPath);

        $this->assertNotEmpty($entries);
        $this->assertInstanceOf(ArchiveEntry::class, $entries[0]);
    }

    #[Test]
    public function it_returns_archive_info(): void
    {
        $zipPath = $this->tempDir . '/info.zip';
        $this->archiver->compress($this->fixtureDir, $zipPath);

        $info = $this->archiver->info($zipPath);

        $this->assertInstanceOf(ArchiveInfo::class, $info);
        $this->assertSame(ArchiveFormat::Zip, $info->format);
        $this->assertSame(3, $info->fileCount);
        $this->assertGreaterThan(0, $info->totalSize);
    }

    #[Test]
    public function it_verifies_archives(): void
    {
        $zipPath = $this->tempDir . '/verify.zip';
        $this->archiver->compress($this->fixtureDir, $zipPath);

        $this->assertTrue($this->archiver->verify($zipPath));
    }

    #[Test]
    public function it_auto_detects_format_for_compress_and_decompress(): void
    {
        // Test that same facade handles multiple formats seamlessly
        $formats = [
            'test_zip.zip',
            'test_tar.tar',
            'test_targz.tar.gz',
        ];

        foreach ($formats as $filename) {
            $archivePath = $this->tempDir . '/' . $filename;
            $extractDir = $this->tempDir . '/extract_' . str_replace('.', '_', $filename);

            $this->archiver->compress($this->fixtureDir, $archivePath);
            $this->assertFileExists($archivePath, "Archive not created: {$filename}");

            $this->archiver->decompress($archivePath, $extractDir);
            $this->assertFileExists($extractDir . '/readme.txt', "Extraction failed for: {$filename}");
        }
    }

}
