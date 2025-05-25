<?php

namespace App\System;

use RuntimeException;

/**
 * A utility class for handling file system operations
 */
class Filer
{
    private const BASEPATH = __DIR__ . '/../';
    /**
     * Reads all files from a directory (non-recursively)
     * 
     * @param string $directory Path to the directory
     * @param array $allowedExtensions Optional array of allowed file extensions (e.g., ['txt', 'pdf'])
     * @return array List of file paths
     * @throws RuntimeException If directory doesn't exist or isn't readable
     */
    public function readFiles(string $directory, array $allowedExtensions = []): array
    {
        $directory = self::BASEPATH . DIRECTORY_SEPARATOR . ltrim($directory, DIRECTORY_SEPARATOR);

        if (!is_dir($directory) || !is_readable($directory)) {
            throw new RuntimeException("Directory $directory does not exist or is not readable");
        }

        $files = [];
        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_file($path)) {
                if (empty($allowedExtensions)) {
                    $files[] = $path;
                } else {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    if (in_array(strtolower($ext), $allowedExtensions, true)) {
                        $files[] = $path;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Writes content to a file
     * 
     * @param string $filePath Full path to the file including filename
     * @param mixed $content Data to write to the file
     * @return bool True on success, false on failure
     * @throws RuntimeException If directory isn't writable
     */
    public function writeFile(string $filepath, mixed $content): bool
    {
        $fullpath = self::BASEPATH . DIRECTORY_SEPARATOR . ltrim($filepath, DIRECTORY_SEPARATOR);
        $dir = dirname($fullpath);

        if (!is_dir($dir)) {
            throw new RuntimeException("Directory $dir does not exist");
        }

        if (!is_writable($dir)) {
            throw new RuntimeException("Directory $dir is not writable");
        }

        $output = var_export($content, true);
        $content = "<?php\n\n\$links = " . $output . ';' . PHP_EOL;
        $result = file_put_contents($fullpath, $content);

        return $result !== false;
    }

    /**
     * Appends content to a file within the base directory.
     *
     * Safely joins the base path with the provided relative path, then appends the given content
     * to the file. Creates the file if it doesn't exist.
     *
     * @param string $filepath Relative path to the target file (from base directory)
     * @param string $content The content to append to the file
     * 
     * @return bool Returns true on success, false on failure
     * 
     * @throws RuntimeException If the file cannot be written to (though file_put_contents typically just returns false)
     * 
     * @example
     * $success = $instance->appendFile('logs/application.log', "New log entry\n");
     * if (!$success) {
     *     // Handle error
     * }
     * 
     * @see file_put_contents()
     * @uses self::BASEPATH as the root directory for file operations
     * @uses DIRECTORY_SEPARATOR for cross-platform path handling
     */
    public function appendFile(string $filepath, string $content): bool
    {
        $fullpath = self::BASEPATH . DIRECTORY_SEPARATOR . ltrim($filepath, DIRECTORY_SEPARATOR);
        return (bool) file_put_contents($fullpath, $content, FILE_APPEND | LOCK_EX);
    }

    /**
     * Creates a directory with proper permissions
     * 
     * @param string $path Directory path to create
     * @param int $permissions Directory permissions (octal, e.g., 0755)
     * @return bool True if directory was created or already exists
     * @throws RuntimeException If directory creation fails
     */
    public function createDirectory(string $filepath, int $permissions = 0755): bool
    {
        $fullpath = self::BASEPATH . DIRECTORY_SEPARATOR . ltrim($filepath, DIRECTORY_SEPARATOR);

        if (is_dir($fullpath)) {
            return true;
        }

        if (!mkdir($fullpath, $permissions, true) && !is_dir($fullpath)) {
            throw new RuntimeException("Failed to create directory $fullpath");
        }

        return true;
    }

    /**
     * Gets the contents of a file
     * 
     * @param string $filePath Path to the file
     * @return string File contents
     * @throws RuntimeException If file doesn't exist or isn't readable
     */
    public function fileContent(string $filepath): string
    {
        $fullpath = self::BASEPATH . DIRECTORY_SEPARATOR . ltrim($filepath, DIRECTORY_SEPARATOR);

        if (!file_exists($fullpath)) {
            throw new RuntimeException("File $fullpath does not exist");
        }

        if (!is_readable($fullpath)) {
            throw new RuntimeException("File $fullpath is not readable");
        }

        return file_get_contents($fullpath);
    }
}
