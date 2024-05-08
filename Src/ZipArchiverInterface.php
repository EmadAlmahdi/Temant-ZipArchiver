<?php declare(strict_types=1);

namespace Temant\ZipArchiver {

    interface ZipArchiverInterface
    {
        /**
         * Create a ZIP file from a directory, optionally applying password protection and encryption.
         *
         * @param string $sourceFolder Absolute path to the source directory.
         * @param string $destinationZip Absolute path where the ZIP file should be stored.
         * @param string|null $password Optional password for ZIP encryption. If null, no encryption is applied.
         * @return bool Returns true if the ZIP file was created successfully, otherwise false.
         */
        public function compress(string $sourceFolder, string $destinationZip, ?string $password = null): bool;

        /**
         * Extract the contents of a ZIP file to a specified directory, optionally using a password for encrypted archives.
         *
         * @param string $archivePath Absolute path to the ZIP file.
         * @param string $destinationPath Absolute path to the directory where the contents should be extracted.
         * @param string|null $password Optional password for decrypting the ZIP file. If null, no decryption is attempted.
         * @return bool Returns true if the extraction was successful, otherwise false.
         */
        public function decompress(string $archivePath, string $destinationPath, ?string $password = null): bool;
    }

}