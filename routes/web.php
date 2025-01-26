<?php

use Illuminate\Support\Facades\Route;
use App\Services\ManufacturerImportService;
use App\Services\CarImportService;

Route::get('/', function () {
    // return view('welcome');
    // Define the paths to the CSV files
    $filePathManufacturers = storage_path('app/manufacturers.csv');
    $filePathCars = storage_path('app/cars.csv'); // Corrected to 'cars.csv'

    // Initialize the ManufacturerImportService
    $importManufacturerService = new ManufacturerImportService();

    // Attempt to import manufacturers
    $manufacturerResult = $importManufacturerService->import($filePathManufacturers);

    if ($manufacturerResult['success']) {
        // If manufacturers imported successfully, proceed to import cars
        $importCarService = new CarImportService();
        $carResult = $importCarService->import($filePathCars);

        if ($carResult['success']) {
            // Both imports succeeded
            return response('<p>Finished database insert successfully</p>', 200)
                ->header('Content-Type', 'text/html');
        } else {
            // Car import failed; return the error message from carResult
            return response('<p>Error: ' . htmlspecialchars($carResult['error']) . '</p>', 500)
                ->header('Content-Type', 'text/html');
        }
    } else {
        // Manufacturer import failed; return the error message from manufacturerResult
        return response('<p>Error: ' . htmlspecialchars($manufacturerResult['error']) . '</p>', 500)
            ->header('Content-Type', 'text/html');
    }

});
