# Temant Archiver

[![CI](https://github.com/EmadAlmahdi/Temant-ZipArchiver/actions/workflows/ci.yml/badge.svg)](https://github.com/EmadAlmahdi/Temant-ZipArchiver/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/temant/archiver/v/stable)](https://packagist.org/packages/temant/archiver)
[![Total Downloads](https://poser.pugx.org/temant/archiver/downloads)](https://packagist.org/packages/temant/archiver)
[![License](https://poser.pugx.org/temant/archiver/license)](https://packagist.org/packages/temant/archiver)
[![PHP Version Require](https://poser.pugx.org/temant/archiver/require/php)](https://packagist.org/packages/temant/archiver)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://phpstan.org/)

A general-purpose compression/decompression library for PHP 8.1+ supporting ZIP, TAR, TAR.GZ, TAR.BZ2, GZIP, BZIP2, and RAR formats with a unified API.

## Features

- **7 archive formats** -- ZIP, TAR, TAR.GZ, TAR.BZ2, GZIP, BZIP2, RAR (decompress only)
- **Unified facade** -- single `Archiver` class handles all formats transparently
- **Auto-detection** -- format resolved automatically from file extension
- **Driver architecture** -- strategy pattern with per-format drivers, easily extensible
- **Password protection** -- AES-256 encryption for ZIP archives
- **Compression levels** -- 6 levels from `Fastest` to `Best` via a clean enum
- **Include/exclude filters** -- glob-based patterns for selective compress/extract
- **Progress callbacks** -- real-time feedback during compress and decompress operations
- **Archive inspection** -- list entries, get metadata, compression ratios, search/filter
- **Archive verification** -- test integrity without extracting
- **Archive comments** -- read and write comments (ZIP)
- **Custom drivers** -- register your own format drivers via the factory
- **PHPStan max level** -- fully statically analysed with zero errors
- **Zero dependencies** -- only requires `ext-phar` (format extensions are optional)

## Requirements

- PHP 8.1 or higher
- [Composer](https://getcomposer.org/)
- `ext-phar` (bundled with PHP)

Optional extensions (required only for their respective formats):

| Extension | Formats |
|---|---|
| `ext-zip` | ZIP |
| `ext-zlib` | GZIP, TAR.GZ |
| `ext-bz2` | BZIP2, TAR.BZ2 |
| `ext-rar` | RAR (decompress only) |

## Installation

```bash
composer require temant/archiver
```

## Quick Start

```php
use Temant\Archiver\Archiver;

$archiver = new Archiver();

// Compress a directory to ZIP (format auto-detected from extension)
$archiver->compress('/path/to/directory', '/path/to/archive.zip');

// Decompress
$archiver->decompress('/path/to/archive.zip', '/path/to/output');
```

## Usage

### Creating the Archiver

```php
use Temant\Archiver\Archiver;

$archiver = new Archiver();
```

The `Archiver` facade auto-detects the format from the file extension and delegates to the appropriate driver.

### Compressing Files

#### Directory to Archive

```php
// ZIP
$archiver->compress('/path/to/directory', '/output/archive.zip');

// TAR
$archiver->compress('/path/to/directory', '/output/archive.tar');

// TAR.GZ
$archiver->compress('/path/to/directory', '/output/archive.tar.gz');

// TAR.BZ2
$archiver->compress('/path/to/directory', '/output/archive.tar.bz2');
```

#### Single File (GZIP / BZIP2)

```php
// GZIP -- single file only
$archiver->compress('/path/to/file.log', '/output/file.log.gz');

// BZIP2 -- single file only
$archiver->compress('/path/to/file.log', '/output/file.log.bz2');
```

### Decompressing Archives

```php
// Extract to a directory
$archiver->decompress('/path/to/archive.zip', '/output/directory');

// Works with any supported format
$archiver->decompress('/path/to/archive.tar.gz', '/output/directory');
$archiver->decompress('/path/to/archive.rar', '/output/directory');
```

### Compression Levels

```php
use Temant\Archiver\Enum\CompressionLevel;

$archiver->compress($source, $dest, [
    'level' => CompressionLevel::Best,    // Maximum compression
]);

// Available levels: None, Fastest, Fast, Normal, Good, Best
```

### Password Protection (ZIP)

```php
// Compress with AES-256 encryption
$archiver->compress($source, 'secret.zip', [
    'password' => 'my-secret-password',
]);

// Decompress with password
$archiver->decompress('secret.zip', $output, [
    'password' => 'my-secret-password',
]);
```

### Include / Exclude Filters

```php
// Only include PHP files
$archiver->compress($source, 'code.zip', [
    'include' => ['*.php'],
]);

// Exclude log files and the vendor directory
$archiver->compress($source, 'project.zip', [
    'exclude' => ['*.log', 'vendor/*'],
]);

// Filters also work on decompress
$archiver->decompress('archive.zip', $output, [
    'include' => ['*.txt'],
]);
```

### Progress Callbacks

```php
$archiver->compress($source, $dest, [
    'progress' => function (string $currentFile, int $processed, int $total): void {
        echo "{$currentFile}: {$processed}/{$total}\n";
    },
]);
```

### Archive Comments (ZIP)

```php
$archiver->compress($source, 'archive.zip', [
    'comment' => 'Release v1.2.0',
]);
```

### Listing Archive Entries

```php
use Temant\Archiver\DTO\ArchiveEntry;

$entries = $archiver->list('/path/to/archive.zip');

foreach ($entries as $entry) {
    echo $entry->path;           // relative path inside archive
    echo $entry->size;           // uncompressed size in bytes
    echo $entry->compressedSize; // compressed size in bytes
    echo $entry->isDirectory;    // bool
    echo $entry->modifiedTime;   // Unix timestamp
    echo $entry->compressionRatio(); // e.g. 0.65
}
```

### Archive Information

```php
use Temant\Archiver\DTO\ArchiveInfo;

$info = $archiver->info('/path/to/archive.zip');

echo $info->format;               // ArchiveFormat::Zip
echo $info->fileCount;            // number of files
echo $info->directoryCount;       // number of directories
echo $info->totalSize;            // total uncompressed size
echo $info->compressedSize;       // total compressed size
echo $info->compressionRatio();   // overall ratio
echo $info->formattedTotalSize(); // e.g. "1.24 MB"

// Search and filter entries
$phpFiles = $info->searchEntries('*.php');
$byExtension = $info->entriesByExtension('json');
```

### Verifying Archives

```php
$isValid = $archiver->verify('/path/to/archive.zip'); // true or false
```

### Format Detection

```php
use Temant\Archiver\Enum\ArchiveFormat;

$format = $archiver->detectFormat('/path/to/archive.tar.gz');
// ArchiveFormat::TarGz

// Check format capabilities
$format->supportsDirectories(); // true
$format->supportsPassword();    // false
$format->supportsCompression(); // true
$format->isDecompressOnly();    // false
```

### Using Specific Drivers

```php
$driver = $archiver->driver(ArchiveFormat::Zip);
$driver->compress($source, $dest, $options);
```

### Registering Custom Drivers

```php
use Temant\Archiver\ArchiverFactory;
use Temant\Archiver\Contract\ArchiverInterface;

$factory = $archiver->factory();
$factory->register(new MyCustomArchiver());
```

### Supported Formats

```php
// All registered formats
$archiver->supportedFormats(); // [ArchiveFormat::Zip, ArchiveFormat::Tar, ...]
```

## Supported Formats

| Format | Extension(s) | Compress | Decompress | Directories | Password | Extension Required |
|---|---|---|---|---|---|---|
| ZIP | `.zip` | Yes | Yes | Yes | AES-256 | `ext-zip` |
| TAR | `.tar` | Yes | Yes | Yes | No | `ext-phar` |
| TAR.GZ | `.tar.gz`, `.tgz` | Yes | Yes | Yes | No | `ext-zlib` |
| TAR.BZ2 | `.tar.bz2`, `.tbz2` | Yes | Yes | Yes | No | `ext-bz2` |
| GZIP | `.gz`, `.gzip` | Yes | Yes | No | No | `ext-zlib` |
| BZIP2 | `.bz2`, `.bzip2` | Yes | Yes | No | No | `ext-bz2` |
| RAR | `.rar` | No | Yes | Yes | Yes | `ext-rar` |

## Exception Handling

All exceptions extend `ArchiverException` (which extends `RuntimeException`).

| Exception | When |
|---|---|
| `CompressionException` | Compression fails (invalid source, write error, unsupported operation) |
| `DecompressionException` | Decompression fails (invalid archive, password error, write error) |
| `UnsupportedFormatException` | Unrecognized file extension or missing PHP extension |
| `ArchiverException` | Base exception for general archive errors |

```php
use Temant\Archiver\Exception\CompressionException;
use Temant\Archiver\Exception\UnsupportedFormatException;

try {
    $archiver->compress($source, 'archive.xyz');
} catch (UnsupportedFormatException $e) {
    // Unknown format
} catch (CompressionException $e) {
    // Compression failed
}
```

## API Reference

### Archiver (Facade)

| Method | Description |
|---|---|
| `compress(source, destination, options?)` | Compress a file or directory |
| `decompress(archive, destination, options?)` | Decompress an archive |
| `list(archive, options?)` | List entries without extracting |
| `info(archive)` | Get archive metadata |
| `verify(archive)` | Test archive integrity |
| `detectFormat(path)` | Detect format from file extension |
| `supportedFormats()` | List all supported formats |
| `driver(format)` | Get a specific format driver |
| `factory()` | Access the underlying factory |

### Options

| Option | Type | Applies To | Description |
|---|---|---|---|
| `password` | `string` | ZIP, RAR | Encryption password |
| `level` | `CompressionLevel` | All | Compression level enum |
| `include` | `string[]` | ZIP, TAR | Glob patterns to include |
| `exclude` | `string[]` | ZIP, TAR | Glob patterns to exclude |
| `comment` | `string` | ZIP | Archive comment |
| `overwrite` | `bool` | ZIP | Overwrite existing files (default: true) |
| `progress` | `callable` | All | Progress callback |

### Enums

#### `ArchiveFormat`

Cases: `Zip`, `Tar`, `TarGz`, `TarBz2`, `Gz`, `Bz2`, `Rar`

Methods: `fromPath()`, `extension()`, `supportsDirectories()`, `supportsPassword()`, `supportsCompression()`, `isDecompressOnly()`, `label()`

#### `CompressionLevel`

Cases: `None` (0), `Fastest` (1), `Fast` (3), `Normal` (5), `Good` (7), `Best` (9)

## Testing

```bash
# Run tests
vendor/bin/phpunit

# Run tests with coverage
vendor/bin/phpunit --coverage-text

# Run static analysis
vendor/bin/phpstan analyse

# Run both
vendor/bin/phpunit && vendor/bin/phpstan analyse
```

## License

MIT License. See [LICENSE](LICENSE) for details.
