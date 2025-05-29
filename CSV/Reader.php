<?php

namespace App\CSV;

use Exception;

/**
 * A utility class for reading and parsing CSV files with various options.
 * 
 * This class provides methods to read CSV files, handle headers, and access data
 * in multiple formats including by row, column, or as a complete dataset.
 *
 */
class Reader
{
    private const BASEPATH = __DIR__ . '/../';

    /** @var string The path to the CSV file */
    private string $filepath;

    /** @var string Field delimiter character */
    private string $delimiter;

    /** @var string Field enclosure character */
    private string $enclosure;

    /** @var string Escape character */
    private string $escape;

    /** @var array The headers from the CSV file if present */
    private array $headers;

    /** @var array The parsed data from the CSV file */
    private array $data;

    /**
     * Constructs a new CsvReader instance.
     *
     * @param string $filepath Path to the CSV file
     * @param string $delimiter Field delimiter (default: ',')
     * @param string $enclosure Field enclosure character (default: '"')
     * @param string $escape Escape character (default: '\\')
     */
    public function __construct(
        string $filepath,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\'
    ) {
        $this->filepath = self::BASEPATH . DIRECTORY_SEPARATOR . ltrim($filepath, DIRECTORY_SEPARATOR);
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
        $this->headers = [];
        $this->data = [];
    }

    /**
     * Reads and parses the CSV file.
     *
     * @param bool $hasHeaders Whether the CSV has headers in the first row (default: true)
     * @return self Returns the current instance for method chaining
     * @throws Exception If file cannot be opened or is not readable
     */
    public function read($hasHeaders = true): self
    {
        if (!file_exists($this->filepath) || !is_readable($this->filepath)) {
            throw new Exception("File not found or not readable: " . $this->filepath);
        }

        $file = fopen($this->filepath, 'r');

        if ($hasHeaders) {
            $this->headers = fgetcsv($file, 0, $this->delimiter, $this->enclosure, $this->escape);
        }

        while (($row = fgetcsv($file, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            if ($hasHeaders && !empty($this->headers)) {
                $this->data[] = array_combine($this->headers, $row);
            } else {
                $this->data[] = $row;
            }
        }

        fclose($file);
        return $this;
    }

    /**
     * Gets all data from the CSV file.
     *
     * @return array The parsed CSV data. If headers were used, each row will be an associative array.
     *               Otherwise, each row will be a numeric array.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Gets the headers from the CSV file.
     *
     * @return array The headers if they exist, otherwise an empty array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Gets the number of data rows in the CSV (excluding headers).
     *
     * @return int The count of data rows
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Gets a specific row by its index.
     *
     * @param int $index The zero-based index of the row to retrieve
     * @return array|null The requested row as an array, or null if index is out of bounds
     */
    public function getRow($index): array|null
    {
        return $this->data[$index] ?? null;
    }

    /**
     * Gets all values from a specific column by its name.
     *
     * @param string $columnName The name of the column to retrieve
     * @return array An array of values from the specified column
     * @throws Exception If CSV has no headers or specified column doesn't exist
     */
    public function getColumn($columnName): array
    {
        if (empty($this->headers)) {
            throw new Exception("Cannot get column by name - CSV has no headers");
        }

        if (!in_array($columnName, $this->headers)) {
            throw new Exception("Column '$columnName' not found in CSV headers");
        }

        return array_column($this->data, $columnName);
    }

    /**
     * Combines all CSV files in a directory into one output file while preserving headers from the first file.
     *
     * @param string $outputpath Path to save the combined CSV file
     * @param string $inputpath Directory containing CSV files to combine
     * @param bool $hasHeaders Whether the CSV files have headers (default: true)
     * @return bool Returns true on success, false on failure
     * @throws Exception If directory cannot be read or files cannot be processed
     */
    public function combineFiles(
        string $outputpath,
        string $inputpath,
        bool $hasHeaders = true
    ): bool {

        $inputpath = self::BASEPATH . DIRECTORY_SEPARATOR . ltrim($inputpath, DIRECTORY_SEPARATOR);
        $outputpath = self::BASEPATH . DIRECTORY_SEPARATOR . ltrim($outputpath, DIRECTORY_SEPARATOR);

        if (!is_dir($inputpath) || !is_readable($inputpath)) {
            throw new Exception("Input directory not found or not readable: " . $inputpath);
        }

        $files = glob($inputpath . DIRECTORY_SEPARATOR . '*.csv');
        if (empty($files)) {
            throw new Exception("No CSV files found in directory: " . $inputpath);
        }

        $outputHandle = fopen($outputpath, 'w');
        if (!$outputHandle) {
            throw new Exception("Could not create output file: " . $outputpath);
        }

        $firstFile = true;
        $headers = [];

        foreach ($files as $file) {
            $handle = fopen($file, 'r');
            if (!$handle) {
                fclose($outputHandle);
                throw new Exception("Could not open file: " . basename($file));
            }

            if ($hasHeaders) {
                $currentHeaders = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);

                if ($firstFile) {
                    $headers = $currentHeaders;
                    fputcsv($outputHandle, $headers, $this->delimiter, $this->enclosure, $this->escape);
                    $firstFile = false;
                } elseif ($currentHeaders !== $headers) {
                    fclose($handle);
                    fclose($outputHandle);
                    throw new Exception("Header mismatch in file: " . basename($file));
                }
            }

            while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
                fputcsv($outputHandle, $row, $this->delimiter, $this->enclosure, $this->escape);
            }

            fclose($handle);
        }

        fclose($outputHandle);
        return true;
    }
}
