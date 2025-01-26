<?php

namespace App\Services;

use App\Helpers\ArrayHelpers;
use App\Models\Car;
use App\Models\Manufacturer;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class CarImportService
{
    /**
     * Import cars from a CSV file.
     *
     * @param string $filePath The path to the CSV file.
     * @return array Contains success or error message.
     */
    public function import(string $filePath): array
    {
        try {
            // Begin a database transaction to ensure atomicity
            DB::beginTransaction();

            // Process the CSV file in chunks to optimize memory usage
            foreach (ArrayHelpers::chunkFile($filePath, [$this, 'processRow'], 1000) as $chunk) {
                try {
                    // Bulk insert the processed chunk into the cars table
                    Car::insert($chunk);
                } catch (QueryException $e) {
                    // Handle integrity constraint violations or other query exceptions
                    if ($e->getCode() === '23000') { // Integrity constraint violation
                        throw new RuntimeException("Import failed due to database integrity constraints.");
                    }
                    throw $e; // Re-throw if it's a different type of QueryException
                }
            }

            // Commit the transaction after successful import
            DB::commit();

            return ['success' => true, 'message' => 'Import completed successfully.'];
        } catch (Exception $e) {
            // Rollback the transaction in case of any errors during import
            DB::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process and validate a single row of car data.
     *
     * @param array $row The raw row data from the CSV.
     *
     * @return array The validated and prepared row for insertion.
     *
     * @throws InvalidArgumentException If validation fails.
     * @noinspection NullPointerExceptionInspection*/
    public function processRow(array $row): array
    {
        // Ensure the row has exactly 5 columns: id, manufacturer, model, year, colour
        if (count($row) !== 5) {
            throw new InvalidArgumentException('Row data is incomplete.');
        }

        // Destructure the row for clarity
        [$id, $manufacturerName, $model, $year, $colour] = $row;

        // Trim and sanitize input data
        $manufacturerName = Str::squish($manufacturerName);
        $model = Str::squish($model);
        $colour = Str::squish($colour);

        // Validate required fields are not empty
        if (empty($manufacturerName) || empty($model) || empty($year) || empty($colour)) {
            throw new InvalidArgumentException('One or more required fields are empty.');
        }

        // Validate 'year' is a valid four-digit year
        if (!preg_match('/^\d{4}$/', $year)) {
            throw new InvalidArgumentException("Invalid year format: $year");
        }

        // Validate 'colour' length
        if (Str::length($colour) > 50) { // Assuming a max length of 50
            throw new InvalidArgumentException('Colour exceeds maximum length of 50 characters.');
        }

        // Validate 'model' length
        if (Str::length($model) > 255) {
            throw new InvalidArgumentException('Model exceeds maximum length of 255 characters.');
        }

        // Validate 'year' and convert it to a date using Carbon
        try {
            // Create a Carbon instance representing January 1st of the given year
            $yearDate = Carbon::createFromFormat('Y', $year)->startOfYear()->toDateString();
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid year value: $year");
        }

        // Lookup the manufacturer by name to get its ID
        $manufacturer = Manufacturer::where('name', $manufacturerName)->first();

        if (!$manufacturer) {
            throw new InvalidArgumentException("Manufacturer not found: $manufacturerName");
        }

        // Prepare the car data for insertion
        return [
            'manufacturer_id' => $manufacturer->id,
            'model' => $model,
            'year' => $yearDate,
            'colour' => $colour,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
