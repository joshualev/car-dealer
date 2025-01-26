<?php

// tests/Feature/ManufacturerImportServiceTest.php

use App\Models\Manufacturer;
use App\Services\ManufacturerImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Initialize the ManufacturerImportService before each test.
 */
beforeEach(function () {
    $this->service = app(ManufacturerImportService::class);
});

/**
 * Helper function to create CSV content.
 *
 * @param array $rows Array of associative arrays representing CSV rows.
 * @return string CSV formatted string.
 */
function createCsvContent(array $rows): string
{
    if (empty($rows)) {
        return "id,manufacturer,description,country\n";
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

    $rows = [
        ['id' => '1', 'manufacturer' => 'TestCo', 'description' => 'Test description', 'country' => 'Germany'],
        ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'United States'],
        ['id' => '3', 'manufacturer' => 'CarNation', 'description' => 'Affordable family cars', 'country' => 'Japan'],
    ];

    $csvContent = createCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('manufacturers_valid.csv', $csvContent);
    Storage::disk('local')->put('manufacturers_valid.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('manufacturers_valid.csv'));

    expect($result)->toBe(['success' => true, 'message' => 'Import completed successfully.'])
                   ->and(Manufacturer::count())->toBe(3)
                   ->and(Manufacturer::find(1))->name->toBe('TestCo')
                                                     ->and(Manufacturer::find(2))->country->toBe('United States')
                                                                                          ->and(Manufacturer::find(3))->country->toBe('Japan');
});

/**
 * @test
 * Rejects invalid countries in the dataset.
 */
it('rejects invalid countries', function () {
    Storage::fake('local');

    $rows = [
        ['id' => '1', 'manufacturer' => 'TestCo', 'description' => 'Test description', 'country' => 'InvalidCountry'],
        ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'Germany'],
    ];

    $csvContent = createCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('manufacturers_invalid_country.csv', $csvContent);
    Storage::disk('local')->put('manufacturers_invalid_country.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('manufacturers_invalid_country.csv'));

    expect($result)->toBe(['success' => false, 'error' => 'Invalid country: InvalidCountry'])
                   ->and(Manufacturer::count())->toBe(0);
});

/**
 * @test
 * Rejects duplicate manufacturer names.
 */
it('rejects duplicate manufacturers', function () {
    Storage::fake('local');

    // Create an existing manufacturer with 'country'
    Manufacturer::create([
        'name' => 'ExistingCo',
        'description' => 'Existing description',
        'country' => 'France',
    ]);

    $rows = [
        ['id' => '1', 'manufacturer' => 'ExistingCo', 'description' => 'Test description', 'country' => 'Germany'], // Duplicate
        ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'United States'],
    ];

    $csvContent = createCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('manufacturers_duplicate.csv', $csvContent);
    Storage::disk('local')->put('manufacturers_duplicate.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('manufacturers_duplicate.csv'));

    expect($result)->toBe(['success' => false, 'error' => 'Import failed due to duplicate manufacturers in the dataset.'])
                   ->and(Manufacturer::count())->toBe(1); // Only ExistingCo exists
});

/**
 * @test
 * Handles empty CSV files gracefully.
 */
it('handles empty CSV files gracefully', function () {
    Storage::fake('local');

    $csvContent = "id,manufacturer,description,country\n"; // Only headers
    $file = UploadedFile::fake()->createWithContent('manufacturers_empty.csv', $csvContent);
    Storage::disk('local')->put('manufacturers_empty.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('manufacturers_empty.csv'));

    expect($result)->toBe(['success' => true, 'message' => 'Import completed successfully.'])
                   ->and(Manufacturer::count())->toBe(0);
});

/**
 * @test
 * Rejects CSV files with incomplete rows.
 */
it('rejects CSV files with incomplete rows', function () {
    Storage::fake('local');

    $rows = [
        ['id' => '1', 'manufacturer' => 'TestCo', 'description' => 'Test description'], // Missing 'country'
        ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'United States'],
    ];

    $csvContent = createCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('manufacturers_incomplete.csv', $csvContent);
    Storage::disk('local')->put('manufacturers_incomplete.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('manufacturers_incomplete.csv'));

    expect($result)->toBe(['success' => false, 'error' => 'Row data is incomplete.'])
                   ->and(Manufacturer::count())->toBe(0);
});

/**
 * @test
 * Rejects duplicate entries within the same CSV file.
 */
it('rejects duplicate entries within the same CSV file', function () {
    Storage::fake('local');

    $rows = [
        ['id' => '1', 'manufacturer' => 'TestCo', 'description' => 'Test description', 'country' => 'Germany'],
        ['id' => '2', 'manufacturer' => 'TestCo', 'description' => 'Another description', 'country' => 'France'], // Duplicate within CSV
        ['id' => '3', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'United States'],
    ];

    $csvContent = createCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('manufacturers_duplicate_within.csv', $csvContent);
    Storage::disk('local')->put('manufacturers_duplicate_within.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('manufacturers_duplicate_within.csv'));

    expect($result)->toBe(['success' => false, 'error' => 'Import failed due to duplicate manufacturers in the dataset.'])
                   ->and(Manufacturer::count())->toBe(0);
});

/**
 * @test
 * Rejects rows with invalid data types.
 */
it('rejects rows with invalid data types', function () {
    Storage::fake('local');

    // Create a `name` that exceeds 255 characters
    $longName = Str::repeat('A', 256); // 256 characters

    $rows = [
        ['id' => '1', 'manufacturer' => $longName, 'description' => 'Test description', 'country' => 'Germany'], // Invalid
        ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'United States'],
    ];

    $csvContent = createCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('manufacturers_invalid_types.csv', $csvContent);
    Storage::disk('local')->put('manufacturers_invalid_types.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('manufacturers_invalid_types.csv'));

    expect($result)->toBe(['success' => false, 'error' => 'Name exceeds maximum length of 255 characters.'])
                   ->and(Manufacturer::count())->toBe(0);
});

/**
 * @test
 * Ensures transaction rollback on failure.
 */
it('ensures transaction rollback on failure', function () {
    Storage::fake('local');

    $rows = [
        ['id' => '1', 'manufacturer' => 'TestCo', 'description' => 'Test description', 'country' => 'Germany'],
        ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'InvalidCountry'], // Invalid
        ['id' => '3', 'manufacturer' => 'CarNation', 'description' => 'Affordable family cars', 'country' => 'Japan'],
    ];

    $csvContent = createCsvContent($rows);
    $file = UploadedFile::fake()->createWithContent('manufacturers_transaction_fail.csv', $csvContent);
    Storage::disk('local')->put('manufacturers_transaction_fail.csv', $file->getContent());

    $result = $this->service->import(Storage::disk('local')->path('manufacturers_transaction_fail.csv'));

    expect($result)->toBe(['success' => false, 'error' => 'Invalid country: InvalidCountry'])
                   ->and(Manufacturer::count())->toBe(0);
});
