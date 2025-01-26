<?php

namespace App\Services;

use App\Helpers\ArrayHelpers;
use App\Models\Manufacturer;
use App\Rules\ValidCountry;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ManufacturerImportService
{
    /**
     * Import manufacturers from a CSV file.
     *
     * @param string $filePath The path to the CSV file.
     * @return array Contains success or error message.
     */
    public function import(string $filePath): array
    {
        try {
            // Begin a database transaction
            DB::beginTransaction();

            // Process the file in chunks
            foreach (ArrayHelpers::chunkFile($filePath, [$this, 'processRow'], 1000) as $chunk) {
                try {
                    Manufacturer::insert($chunk);
                } catch (QueryException $e) {
                    if ($e->getCode() === '23000') { // Duplicate entry error
                        throw new RuntimeException("Import failed due to duplicate manufacturers in the dataset.");
                    }
                    throw $e;
                }
            }

            // Commit the transaction
            DB::commit();
            return ['success' => true, 'message' => 'Import completed successfully.'];
        } catch (Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process and validate a single row of manufacturer data.
     *
     * @param array $row The raw row data from the CSV.
     * @return array The validated and prepared row.
     *
     * @throws InvalidArgumentException If validation fails.
     */
    public function processRow(array $row): array
    {
        // Ensure the row has at least 4 columns: id, name, description, country
        if (count($row) < 4) {
            throw new InvalidArgumentException('Row data is incomplete.');
        }

        // Assign columns to variables for clarity
        [$id, $name, $description, $country] = $row;

        // Trim and sanitize input data
        $name = Str::squish($name);
        $description = Str::squish($description);
        $country = Str::squish($country);

        // Validate required fields are not empty
        if (empty($name)) {
            throw new InvalidArgumentException('Name cannot be empty.');
        }

        // Validate 'name' length
        if (Str::length($name) > 255) {
            throw new InvalidArgumentException('Name exceeds maximum length of 255 characters.');
        }

        // Validate 'description' length
        if (Str::length($description) > 255) {
            throw new InvalidArgumentException('Description exceeds maximum length of 255 characters.');
        }

        // Validate 'country' using the ValidCountry rule
        $validCountryRule = new ValidCountry();
        $fails = false;
        $validCountryRule->validate('country', $country, function () use (&$fails) {
            $fails = true;
        });

        if ($fails) {
            throw new InvalidArgumentException("Invalid country: $country");
        }

        // Use the normalized country name
        $normalizedCountry = $validCountryRule->getNormalized();

        return [
            'name' => $name,
            'description' => $description,
            'country' => $normalizedCountry,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
