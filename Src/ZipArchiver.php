<?php declare(strict_types=1);

namespace Temant\ZipArchiver {

    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use ZipArchive;

    class ZipArchiver implements ZipArchiverInterface
    {
        /**
         * Create a ZIP file from a directory, optionally applying password protection and encryption.
         *
         * This method walks through the directory structure recursively, adding each file to the ZIP archive.
         * If a password is provided, AES-256 encryption is applied. The file structure within the archive is preserved as relative paths.
         * 
         * @param string $sourceFolder Absolute path to the source directory.
         * @param string $destinationZip Absolute path where the ZIP file should be stored.
         * @param string|null $password Optional password for ZIP encryption. If null, no encryption is applied.
         * @return bool Returns true if the ZIP file was created successfully, otherwise false.
         */
        public function compress(string $sourceFolder, string $destinationZip, ?string $password = null): bool
        {
            $zip = new ZipArchive();

            // Attempt to open the ZIP file for creation. If it cannot be opened, return false.
            if ($zip->open($destinationZip, ZipArchive::CREATE) !== TRUE) {
                return false;
            }

            // If a password is provided, set the password for the ZIP archive.
            if ($password !== null) {
                $zip->setPassword($password);
            }

            // Create a recursive directory iterator to iterate over all files in the source folder,
            // skipping directory dots (. and ..).
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceFolder, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            // Get the absolute path of the source folder to calculate relative paths of files.
            $sourcePath = realpath($sourceFolder);

            // Iterate over each file in the directory structure.
            foreach ($iterator as $file) {
                // Exclude directories from being added to the ZIP.
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourcePath) + 1);

                    // Add the file to the ZIP archive with its relative path.
                    $zip->addFile($filePath, $relativePath);

                    // Set encryption for the file within the ZIP if a password was provided.
                    if ($password !== null) {
                        $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
                    }
                }
            }

            // Close the ZIP archive and confirm the operation was successful.
            $zip->close();

            return true;
        }

        /**
         * Decompress a ZIP archive to a specified directory, optionally using a password.
         *
         * This method attempts to open the ZIP archive and, if a password is provided, applies it.
         * The contents of the archive are then extracted to the given destination directory. 
         * The operation returns true if the extraction is successful, otherwise false.
         *
         * @param string $archivePath Absolute path to the ZIP archive.
         * @param string $destinationPath Absolute path to the directory where the contents should be extracted.
         * @param string|null $password Optional password for decrypting the ZIP archive. If null, no decryption is attempted.
         * @return bool Returns true if the archive was decompressed successfully, otherwise false.
         */
        public function decompress(string $archivePath, string $destinationPath, ?string $password = null): bool
        {
            $zip = new ZipArchive();

            // Attempt to open the ZIP file
            if ($zip->open($archivePath) !== TRUE) {
                return false;
            }

            // Set the password for the ZIP file if one is provided
            if ($password !== null) {
                $zip->setPassword($password);
            }

            // Attempt to extract the ZIP file to the destination directory
            if (!$zip->extractTo($destinationPath)) {
                $zip->close();
                return false;
            }

            // Close the ZIP file after extraction
            $zip->close();
            return true;
        }
    }

}