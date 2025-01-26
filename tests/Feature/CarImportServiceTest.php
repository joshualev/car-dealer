<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Manufacturer;
use App\Services\CarImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Initialize the CarImportService before each test.
 */
beforeEach(function () {
    $this->service = app(CarImportService::class);
});

/**
 * Helper function to create CSV content.
 *
 * @param array $rows Array of associative arrays representing CSV rows.
 * @return string CSV formatted string.
 */
function createCarCsvContent(array $rows): string
{
    if (empty($rows)) {
        return "id,manufacturer,model,year,colour\n";
    }

    $headers = array_keys($rows[0]);
    $csv = implode(',', $headers) . "\n";

    foreach ($rows as $row) {
        $escapedRow = array_map(function ($field) {
            // Escape double quotes by doubling them
            $field = str_replace('"', '""', $field);
            // Enclose fields containing commas or quotes in double quotes
            if (Str::contains($field, [',', '"'])) {
                return "\"{$field}\"";
            }
            return $field;
        }, $row);

        $csv .= implode(',', $escapedRow) . "\n";
    }

    return $csv;
}

/**
 * @test
 * Imports valid data correctly.
 */
it('imports valid data correctly', function () {
    Storage::fake('local');

    // Create manufacturers to associate with cars
    $manufacturers = [
        ['name' => 'Mercedes-Benz', 'description' => 'Luxury cars', 'country' => 'Germany'],
        ['name' => 'Plymouth', 'description' => 'Affordable cars', 'country' => 'United States'],
        // Add more manufacturers as needed
    ];

    foreach ($manufacturers as $manu) {
        Manufacturer::create($manu);
    }

    $rows = [
        ['id' => '1', 'manufacturer' => 'Mercedes-Benz', 'model' => 'G-Class', 'year' => '2007', 'colour' => 'Mauv'],
        ['id' => '2', 'manufacturer' => 'Plymouth', 'model' => 'Breeze', 'year' => '2000', 'colour' => 'Red'],
    ];

    $csvContent = createCarCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('cars_valid.csv', $csvContent);
    Storage::disk('local')->put('cars_valid.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('cars_valid.csv'));

    expect($result)->toBe(['success' => true, 'message' => 'Import completed successfully.'])
                   ->and(Car::count())->toBe(2)
                   ->and(Car::find(1))->model->toBe('G-Class')
                                             ->and(Car::find(1))->year->toBe('2007-01-01')
                                                                      ->and(Car::find(1))->colour->toBe('Mauv')
                                                                                                 ->and(Car::find(1)->manufacturer->name)->toBe('Mercedes-Benz')
                                                                                                 ->and(Car::find(2))->model->toBe('Breeze')
                                                                                                                           ->and(Car::find(2))->year->toBe('2000-01-01')
                                                                                                                                                    ->and(Car::find(2))->colour->toBe('Red')
                                                                                                                                                                               ->and(Car::find(2)->manufacturer->name)->toBe('Plymouth');
});

/**
 * @test
 * Rejects cars with non-existent manufacturers.
 */
it('rejects cars with non-existent manufacturers', function () {
    Storage::fake('local');

    // Only one manufacturer exists
    Manufacturer::create([
        'name' => 'Mercedes-Benz',
        'description' => 'Luxury cars',
        'country' => 'Germany',
    ]);

    $rows = [
        ['id' => '1', 'manufacturer' => 'Mercedes-Benz', 'model' => 'G-Class', 'year' => '2007', 'colour' => 'Mauv'],
        ['id' => '2', 'manufacturer' => 'UnknownBrand', 'model' => 'ModelX', 'year' => '2020', 'colour' => 'Blue'], // Non-existent
    ];

    $csvContent = createCarCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('cars_invalid_manufacturer.csv', $csvContent);
    Storage::disk('local')->put('cars_invalid_manufacturer.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('cars_invalid_manufacturer.csv'));

    expect($result)->toBe(['success' => false, 'error' => 'Manufacturer not found: UnknownBrand'])
                   ->and(Car::count())->toBe(0); // No cars should be inserted due to rollback
});

/**
 * @test
 * Handles empty CSV files gracefully.
 */
it('handles empty CSV files gracefully', function () {
    Storage::fake('local');

    $csvContent = "id,manufacturer,model,year,colour\n"; // Only headers
    $file = UploadedFile::fake()->createWithContent('cars_empty.csv', $csvContent);
    Storage::disk('local')->put('cars_empty.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('cars_empty.csv'));

    expect($result)->toBe(['success' => true, 'message' => 'Import completed successfully.'])
                   ->and(Car::count())->toBe(0);
});

/**
 * @test
 * Rejects CSV files with incomplete rows.
 */
it('rejects CSV files with incomplete rows', function () {
    Storage::fake('local');

    // Create a manufacturer for valid rows
    Manufacturer::create([
        'name' => 'Plymouth',
        'description' => 'Affordable cars',
        'country' => 'United States',
    ]);

    $rows = [
        ['id' => '1', 'manufacturer' => 'Plymouth', 'model' => 'Acclaim', 'year' => '1995'], // Missing 'colour'
        ['id' => '2', 'manufacturer' => 'Plymouth', 'model' => 'Breeze', 'year' => '2000', 'colour' => 'Red'],
    ];

    $csvContent = createCarCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('cars_incomplete.csv', $csvContent);
    Storage::disk('local')->put('cars_incomplete.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('cars_incomplete.csv'));

    expect($result)->toBe(['success' => false, 'error' => 'Row data is incomplete.'])
                   ->and(Car::count())->toBe(0);
});

/**
 * @test
 * Rejects cars with invalid data types.
 */
it('rejects cars with invalid data types', function () {
    Storage::fake('local');

    // Create a manufacturer for valid rows
    Manufacturer::create([
        'name' => 'Mercedes-Benz',
        'description' => 'Luxury cars',
        'country' => 'Germany',
    ]);

    $rows = [
        ['id' => '1', 'manufacturer' => 'Mercedes-Benz', 'model' => 'S-Class', 'year' => '20A7', 'colour' => 'Black'], // Invalid year
        ['id' => '2', 'manufacturer' => 'Mercedes-Benz', 'model' => 'E-Class', 'year' => '2010', 'colour' => 'White'],
    ];

    $csvContent = createCarCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('cars_invalid_year.csv', $csvContent);
    Storage::disk('local')->put('cars_invalid_year.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('cars_invalid_year.csv'));

    expect($result)->toBe(['success' => false, 'error' => 'Invalid year format: 20A7'])
                   ->and(Car::count())->toBe(0);
});

/**
 * @test
 * Ensures transaction rollback on failure.
 */
it('ensures transaction rollback on failure', function () {
    Storage::fake('local');

    // Create a manufacturer for valid rows
    Manufacturer::create([
        'name' => 'Hyundai',
        'description' => 'Reliable cars',
        'country' => 'South Korea',
    ]);

    $rows = [
        ['id' => '1', 'manufacturer' => 'Hyundai', 'model' => 'Accent', 'year' => '2007', 'colour' => 'Violet'],
        ['id' => '2', 'manufacturer' => 'Hyundai', 'model' => 'Genesis Coupe', 'year' => '2013'], // Missing 'colour'
        ['id' => '3', 'manufacturer' => 'Hyundai', 'model' => 'Elantra', 'year' => '2015', 'colour' => 'Blue'],
    ];

    $csvContent = createCarCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('cars_transaction_fail.csv', $csvContent);
    Storage::disk('local')->put('cars_transaction_fail.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('cars_transaction_fail.csv'));

    expect($result)->toBe(['success' => false, 'error' => 'Row data is incomplete.'])
                   ->and(Car::count())->toBe(0); // No cars should be inserted due to rollback
});
