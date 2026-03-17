<?php

declare(strict_types=1);

namespace Temant\Archiver\Driver;

use Temant\Archiver\Contract\ArchiverInterface;
use Temant\Archiver\Enum\CompressionLevel;

abstract class AbstractArchiver implements ArchiverInterface
{
    /**
     * @param array<string, mixed> $options
     */
    protected function getPassword(array $options): ?string
    {
        return isset($options['password']) && is_string($options['password']) ? $options['password'] : null;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function getCompressionLevel(array $options): CompressionLevel
    {
        return $options['level'] instanceof CompressionLevel ? $options['level'] : CompressionLevel::Normal;
    }

    /**
     * @param array<string, mixed> $options
     * @return string[]
     */
    protected function getIncludePatterns(array $options): array
    {
        if (!isset($options['include']) || !is_array($options['include'])) {
            return [];
        }

        return array_values(array_filter($options['include'], 'is_string'));
    }

    /**
     * @param array<string, mixed> $options
     * @return string[]
     */
    protected function getExcludePatterns(array $options): array
    {
        if (!isset($options['exclude']) || !is_array($options['exclude'])) {
            return [];
        }

        return array_values(array_filter($options['exclude'], 'is_string'));
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function getOverwrite(array $options): bool
    {
        return !isset($options['overwrite']) || $options['overwrite'] !== false;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function getProgressCallback(array $options): ?\Closure
    {
        return isset($options['progress']) && is_callable($options['progress'])
            ? \Closure::fromCallable($options['progress'])
            : null;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function getComment(array $options): ?string
    {
        return isset($options['comment']) && is_string($options['comment']) ? $options['comment'] : null;
    }

    /**
     * Check if a relative file path should be included based on include/exclude patterns.
     *
     * @param string[] $include
     * @param string[] $exclude
     */
    protected function shouldIncludeFile(string $relativePath, array $include, array $exclude): bool
    {
        if ($exclude !== []) {
            foreach ($exclude as $pattern) {
                if (fnmatch($pattern, $relativePath)) {
                    return false;
                }
            }
        }

        if ($include !== []) {
            foreach ($include as $pattern) {
                if (fnmatch($pattern, $relativePath)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * Recursively collect files from a directory, respecting include/exclude patterns.
     *
     * @param string[] $include
     * @param string[] $exclude
     * @return string[] Array of absolute file paths.
     */
    protected function collectFiles(string $sourceDir, array $include = [], array $exclude = []): array
    {
        $files = [];
        $sourcePath = realpath($sourceDir);

        if ($sourcePath === false) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();
            if ($filePath === false) {
                continue;
            }

            $relativePath = substr($filePath, strlen($sourcePath) + 1);
            $relativePath = str_replace("\\", "/", $relativePath);

            if ($this->shouldIncludeFile($relativePath, $include, $exclude)) {
                $files[] = $filePath;
            }
        }

        return $files;
    }

    /**
     * Calculate the relative path of a file within a source directory.
     */
    protected function relativePath(string $filePath, string $sourceDir): string
    {
        $sourcePath = realpath($sourceDir);
        if ($sourcePath === false) {
            return basename($filePath);
        }

        $relative = substr($filePath, strlen($sourcePath) + 1);
        return str_replace("\\", "/", $relative);
    }

    /**
     * Ensure a destination directory exists.
     */
    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}