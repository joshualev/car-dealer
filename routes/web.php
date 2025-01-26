<?php

use Illuminate\Support\Facades\Route;
use App\Services\ManufacturerImportService;

Route::get('/', function () {
    // return view('welcome');
    // csv file path
    $filePath = storage_path('app/manufacturers.csv');

    $importService = new ManufacturerImportService();
    $result = $importService->import($filePath);

    if ($result['success']) {
        echo '<p>Finished database insert successfully</p>';
    } else {
        echo '<p>Error: ' . $result['error'] . '</p>';
    }

});
