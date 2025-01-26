<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Manufacturer;
use App\Services\CarImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->service = app(CarImportService::class);
});

function createCarCsvContent(array $rows): string {
    if (empty($rows)) {
        return "id,manufacturer,model,year,colour\n";
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
describe('Car Import', function () {

    describe('Basic Import Functionality', function() {
        it('imports valid data correctly', function () {
            Storage::fake('local');

            // Create test manufacturers
            $manufacturers = [
                ['name' => 'Mercedes-Benz', 'description' => 'Luxury cars', 'country' => 'Germany'],
                ['name' => 'Plymouth', 'description' => 'Affordable cars', 'country' => 'United States'],
            ];
            foreach ($manufacturers as $manu) {
                Manufacturer::create($manu);
            }

            // Prepare test data
            $rows = [
                ['id' => '1', 'manufacturer' => 'Mercedes-Benz', 'model' => 'G-Class', 'year' => '2007', 'colour' => 'Mauv'],
                ['id' => '2', 'manufacturer' => 'Plymouth', 'model' => 'Breeze', 'year' => '2000', 'colour' => 'Red'],
            ];

            // Create and store the CSV
            $csvContent = createCarCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('cars_valid.csv', $csvContent);
            Storage::disk('local')->put('cars_valid.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('cars_valid.csv'));

            // Assert the result
            expect($result)->toBe([
                'success' => true,
                'message' => 'Import completed successfully.'
            ]);

            // Assert the number of cars
            expect(Car::count())->toBe(2);

            // Assert first car details
            $firstCar = Car::find(1);
            expect($firstCar->model)->toBe('G-Class');
            expect($firstCar->year)->toBe('2007-01-01');
            expect($firstCar->colour)->toBe('Mauv');
            expect($firstCar->manufacturer->name)->toBe('Mercedes-Benz');

            // Assert second car details
            $secondCar = Car::find(2);
            expect($secondCar->model)->toBe('Breeze');
            expect($secondCar->year)->toBe('2000-01-01');
            expect($secondCar->colour)->toBe('Red');
            expect($secondCar->manufacturer->name)->toBe('Plymouth');
        });
    });

    describe('Validation Rules', function() {
        it('rejects cars with non-existent manufacturers', function () {
            Storage::fake('local');

            // Create single manufacturer
            Manufacturer::create([
                'name' => 'Mercedes-Benz',
                'description' => 'Luxury cars',
                'country' => 'Germany',
            ]);

            // Prepare test data with invalid manufacturer
            $rows = [
                ['id' => '1', 'manufacturer' => 'Mercedes-Benz', 'model' => 'G-Class', 'year' => '2007', 'colour' => 'Mauv'],
                ['id' => '2', 'manufacturer' => 'UnknownBrand', 'model' => 'ModelX', 'year' => '2020', 'colour' => 'Blue'],
            ];

            // Create and store the CSV
            $csvContent = createCarCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('cars_invalid_manufacturer.csv', $csvContent);
            Storage::disk('local')->put('cars_invalid_manufacturer.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('cars_invalid_manufacturer.csv'));

            // Assert the results
            expect($result)->toBe([
                'success' => false,
                'error' => 'Manufacturer not found: UnknownBrand'
            ]);
            expect(Car::count())->toBe(0);
        });

        it('rejects incomplete rows', function () {
            Storage::fake('local');

            // Create test manufacturer
            Manufacturer::create([
                'name' => 'Plymouth',
                'description' => 'Affordable cars',
                'country' => 'United States',
            ]);

            // Prepare test data with missing colour
            $rows = [
                ['id' => '1', 'manufacturer' => 'Plymouth', 'model' => 'Acclaim', 'year' => '1995'],
                ['id' => '2', 'manufacturer' => 'Plymouth', 'model' => 'Breeze', 'year' => '2000', 'colour' => 'Red'],
            ];

            // Create and store the CSV
            $csvContent = createCarCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('cars_incomplete.csv', $csvContent);
            Storage::disk('local')->put('cars_incomplete.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('cars_incomplete.csv'));

            // Assert the results
            expect($result)->toBe([
                'success' => false,
                'error' => 'Row data is incomplete.'
            ]);
            expect(Car::count())->toBe(0);
        });
    });

    describe('Data Integrity', function() {
        it('ensures transaction rollback on failure', function () {
            Storage::fake('local');

            // Create test manufacturer
            Manufacturer::create([
                'name' => 'Hyundai',
                'description' => 'Reliable cars',
                'country' => 'South Korea',
            ]);

            // Prepare test data with missing colour in middle row
            $rows = [
                ['id' => '1', 'manufacturer' => 'Hyundai', 'model' => 'Accent', 'year' => '2007', 'colour' => 'Violet'],
                ['id' => '2', 'manufacturer' => 'Hyundai', 'model' => 'Genesis Coupe', 'year' => '2013'], // Missing colour
                ['id' => '3', 'manufacturer' => 'Hyundai', 'model' => 'Elantra', 'year' => '2015', 'colour' => 'Blue'],
            ];

            // Create and store the CSV
            $csvContent = createCarCsvContent($rows);
            $file = UploadedFile::fake()->createWithContent('cars_transaction_fail.csv', $csvContent);
            Storage::disk('local')->put('cars_transaction_fail.csv', $file->getContent());

            // Perform the import
            $result = $this->service->import(Storage::disk('local')->path('cars_transaction_fail.csv'));

            // Assert the results
            expect($result)->toBe([
                'success' => false,
                'error' => 'Row data is incomplete.'
            ]);
            expect(Car::count())->toBe(0);
        });
    });
});
