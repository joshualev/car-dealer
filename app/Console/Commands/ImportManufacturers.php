<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ManufacturerImportService;

class ImportManufacturers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:manufacturers
                            {filePath? : The path to the manufacturers CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import manufacturers from a CSV file';

    /**
     * Execute the console command.
     *
     * @param ManufacturerImportService $importService
     * @return int
     */
    public function handle(ManufacturerImportService $importService)
    {
        // Retrieve the 'filePath' argument; it may be null
        $filePath = $this->argument('filePath');

        // If 'filePath' is not provided, prompt the user to enter it
        if (is_null($filePath)) {
            $filePath = $this->askFilePath();
        }

        // Resolve the absolute path
        $absolutePath = realpath($filePath);

        // Check if the file exists and is readable
        if (!$absolutePath || !is_file($absolutePath) || !is_readable($absolutePath)) {
            $this->error("File not found or is not readable at path: {$filePath}");
            return 1; // Exit with error code
        }

        $this->info("Starting import of manufacturers from: {$absolutePath}");

        // Confirm with the user before proceeding
        if (!$this->confirm("Do you want to proceed with importing manufacturers from '{$absolutePath}'?", true)) {
            $this->info('Import cancelled.');
            return 0; // Exit without error
        }

        // Execute the import process
        $result = $importService->import($absolutePath);

        // Provide feedback based on the import result
        if ($result['success']) {
            $this->info($result['message']);
            return 0; // Exit with success code
        } else {
            $this->error("Import failed: {$result['error']}");
            return 1; // Exit with error code
        }
    }

    /**
     * Prompt the user to enter the file path.
     *
     * @return string
     */
    private function askFilePath(): string
    {
        $this->info('Please provide the path to your CSV file.');
        $this->line('Example: /path/to/your/manufacturers.csv');

        return $this->ask('What is the path to your CSV file?');
    }
}
