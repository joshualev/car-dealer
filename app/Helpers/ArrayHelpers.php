<?php

namespace App\Helpers;

use Generator;
use RuntimeException;

class ArrayHelpers {
    /**
     * Process a file in chunks and yield the results.
     *
     * This method reads a file line by line, processes each line using the
     * provided callback, and yields chunks of processed data. It is memory-efficient
     * because it doesn't load the entire file into memory at once.
     *
     * @param string   $filePath   The file path to the CSV file to be processed.
     * @param callable $generator  A callback function to process each row of the file.
     *                             The function must accept one argument (a single row as an array)
     *                             and return the processed data.
     * @param int      $chunkSize  The number of rows to include in each yielded chunk.
     *
     * @return Generator          A generator yielding chunks of processed rows.
     *
     * @throws RuntimeException   Throws an exception if the file cannot be opened.
     *
     * @link https://www.php.net/manual/en/function.fgetcsv.php PHP fgetcsv documentation
     * @link https://www.php.net/manual/en/language.generators.php PHP Generators overview
     */
    public static function chunkFile(string $filePath, callable $generator, int $chunkSize): Generator
    {
        // Attempt to open the file for reading
        $file = fopen($filePath, 'r');
        if (!$file) {
            throw new RuntimeException("Unable to open file at {$filePath}");
        }

        // Retrieve the first row of the CSV file, which contains the headers.
        // If the file is empty or the headers cannot be read, throw an exception.
        $headers = fgetcsv($file);
        if (!$headers) {
            throw new RuntimeException('CSV is empty or contains invalid headers');
        }

        $data = []; // Initialize the array to hold processed rows

        // Read the file line by line
        for ($ii = 1; ($row = fgetcsv($file, null, ',')) !== false; $ii++) {
            $data[] = $generator($row); // Process the row using the provided callback

            // If chunkSize is reached, yield the chunk and reset the data array
            if ($ii % $chunkSize === 0) {
                yield $data;
                $data = [];
            }
        }

        // After the loop, yield any remaining rows that didn't complete a chunk
        if (!empty($data)) {
            yield $data;
        }

        // Close the file to free resources
        fclose($file);
    }
}
