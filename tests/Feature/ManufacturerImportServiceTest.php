<?php

use App\Models\Manufacturer;
use App\Services\ManufacturerImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->service = app(ManufacturerImportService::class);
});

function createCsvContent(array $rows): string {
    if (empty($rows)) {
        return "id,manufacturer,description,country\n";
    }

    $headers = array_keys($rows[0]);
    $csv = implode(',', $headers) . "\n";

    foreach ($rows as $row) {
        $escapedRow = array_map(function ($field) {
            $field = str_replace('"', '""', $field);
            if (Str::contains($field, [',', '"'])) {
                return "\"{$field}\"";
            }
            return $field;
        }, $row);

        $csv .= implode(',', $escapedRow) . "\n";
    }

    return $csv;
}

describe('Manufacturer Import', function () {

    describe('Basic Import Functionality', function() {
        it('imports valid data correctly', function () {
            Storage::fake('local');

            // Prepare test data
            $rows = [
                ['id' => '1', 'manufacturer' => 'TestCo', 'description' => 'Test description', 'country' => 'Germany'],
                ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'United States'],
                ['id' => '3', 'manufacturer' => 'CarNation', 'description' => 'Affordable family cars', 'country' => 'Japan'],
            ];

            // Create and store the CSV
            $csvContent = createCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('manufacturers_valid.csv', $csvContent);
            Storage::disk('local')->put('manufacturers_valid.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('manufacturers_valid.csv'));

            // Assert the result
            expect($result)->toBe([
                'success' => true,
                'message' => 'Import completed successfully.'
            ]);

            // Assert the number of manufacturers
            expect(Manufacturer::count())->toBe(3);

            // Assert individual manufacturer details
            $firstManufacturer = Manufacturer::find(1);
            expect($firstManufacturer->name)->toBe('TestCo');

            $secondManufacturer = Manufacturer::find(2);
            expect($secondManufacturer->country)->toBe('United States');

            $thirdManufacturer = Manufacturer::find(3);
            expect($thirdManufacturer->country)->toBe('Japan');
        });

        it('handles empty CSV files gracefully', function () {
            Storage::fake('local');

            // Create empty CSV
            $csvContent = "id,manufacturer,description,country\n";
            $file = UploadedFile::fake()->createWithContent('manufacturers_empty.csv', $csvContent);
            Storage::disk('local')->put('manufacturers_empty.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('manufacturers_empty.csv'));

            // Assert results
            expect($result)->toBe([
                'success' => true,
                'message' => 'Import completed successfully.'
            ]);
            expect(Manufacturer::count())->toBe(0);
        });
    });

    describe('Validation Rules', function() {
        it('rejects invalid countries', function () {
            Storage::fake('local');

            // Prepare test data with invalid country
            $rows = [
                ['id' => '1', 'manufacturer' => 'TestCo', 'description' => 'Test description', 'country' => 'InvalidCountry'],
                ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'Germany'],
            ];

            // Create and store the CSV
            $csvContent = createCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('manufacturers_invalid_country.csv', $csvContent);
            Storage::disk('local')->put('manufacturers_invalid_country.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('manufacturers_invalid_country.csv'));

            // Assert results
            expect($result)->toBe([
                'success' => false,
                'error' => 'Invalid country: InvalidCountry'
            ]);
            expect(Manufacturer::count())->toBe(0);
        });

        it('rejects incomplete rows', function () {
            Storage::fake('local');

            // Prepare test data with missing country
            $rows = [
                ['id' => '1', 'manufacturer' => 'TestCo', 'description' => 'Test description'],
                ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'United States'],
            ];

            // Create and store the CSV
            $csvContent = createCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('manufacturers_incomplete.csv', $csvContent);
            Storage::disk('local')->put('manufacturers_incomplete.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('manufacturers_incomplete.csv'));

            // Assert results
            expect($result)->toBe([
                'success' => false,
                'error' => 'Row data is incomplete.'
            ]);
            expect(Manufacturer::count())->toBe(0);
        });

        it('rejects rows with invalid data types', function () {
            Storage::fake('local');

            // Create test data with invalid name length
            $longName = Str::repeat('A', 256);
            $rows = [
                ['id' => '1', 'manufacturer' => $longName, 'description' => 'Test description', 'country' => 'Germany'],
                ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'United States'],
            ];

            // Create and store the CSV
            $csvContent = createCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('manufacturers_invalid_types.csv', $csvContent);
            Storage::disk('local')->put('manufacturers_invalid_types.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('manufacturers_invalid_types.csv'));

            // Assert results
            expect($result)->toBe([
                'success' => false,
                'error' => 'Name exceeds maximum length of 255 characters.'
            ]);
            expect(Manufacturer::count())->toBe(0);
        });
    });

    describe('Duplicate Handling', function() {
        it('rejects duplicate manufacturers', function () {
            Storage::fake('local');

            // Create existing manufacturer
            Manufacturer::create([
                'name' => 'ExistingCo',
                'description' => 'Existing description',
                'country' => 'France',
            ]);

            // Prepare test data with duplicate manufacturer
            $rows = [
                ['id' => '1', 'manufacturer' => 'ExistingCo', 'description' => 'Test description', 'country' => 'Germany'],
                ['id' => '2', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'United States'],
            ];

            // Create and store the CSV
            $csvContent = createCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('manufacturers_duplicate.csv', $csvContent);
            Storage::disk('local')->put('manufacturers_duplicate.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('manufacturers_duplicate.csv'));

            // Assert results
            expect($result)->toBe([
                'success' => false,
                'error' => 'Import failed due to duplicate manufacturers in the dataset.'
            ]);
            expect(Manufacturer::count())->toBe(1);
        });

        it('rejects duplicate entries within the same CSV file', function () {
            Storage::fake('local');

            // Prepare test data with internal duplicate
            $rows = [
                ['id' => '1', 'manufacturer' => 'TestCo', 'description' => 'Test description', 'country' => 'Germany'],
                ['id' => '2', 'manufacturer' => 'TestCo', 'description' => 'Another description', 'country' => 'France'],
                ['id' => '3', 'manufacturer' => 'AutoMax', 'description' => 'Leading automotive innovations', 'country' => 'United States'],
            ];

            // Create and store the CSV
            $csvContent = createCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('manufacturers_duplicate_within.csv', $csvContent);
            Storage::disk('local')->put('manufacturers_duplicate_within.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('manufacturers_duplicate_within.csv'));

            // Assert results
            expect($result)->toBe([
                'success' => false,
                'error' => 'Import failed due to duplicate manufacturers in the dataset.'
            ]);
            expect(Manufacturer::count())->toBe(0);
        });
    });

    describe('Country Normalization', function() {
        it('normalizes country names correctly', function () {
            Storage::fake('local');

            // Prepare test data with various country formats
            $rows = [
                ['id' => '1', 'name' => 'Mercedes-Benz', 'description' => 'German luxury automaker...', 'country' => 'German'],
                ['id' => '2', 'name' => 'Chevrolet', 'description' => "GM's mainstream brand...", 'country' => 'nited States'],
                ['id' => '4', 'name' => 'Hyundai', 'description' => 'South Korean manufacturer...', 'country' => 'South Korean'],
            ];

            // Create and store the CSV
            $csvContent = createCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('manufacturers_test.csv', $csvContent);
            Storage::disk('local')->put('manufacturers_test.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('manufacturers_test.csv'));

            // Assert import success
            expect($result)->toBe([
                'success' => true,
                'message' => 'Import completed successfully.'
            ]);
            expect(Manufacturer::count())->toBe(3);

            // Assert normalized country names
            $firstManufacturer = Manufacturer::find(1);
            expect($firstManufacturer->country)->toBe('Germany');

            $secondManufacturer = Manufacturer::find(2);
            expect($secondManufacturer->country)->toBe('United States');

            $thirdManufacturer = Manufacturer::find(3);
            expect($thirdManufacturer->country)->toBe('South Korea');
        });
    });
});
