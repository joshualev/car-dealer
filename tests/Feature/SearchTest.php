<?php

use App\Models\Car;
use App\Models\Manufacturer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test manufacturer
    $this->manufacturer = Manufacturer::factory()->create([
        'name' => 'Toyota',
        'country' => 'Japan'
    ]);

    // Create test cars
    $this->car = Car::factory()->create([
        'manufacturer_id' => $this->manufacturer->id,
        'model' => 'Camry',
        'year' => '2023-01-01',
        'colour' => 'Red'
    ]);
});

describe('Search Page', function () {

    describe('Basic Search Functionality', function () {
        test('search page loads correctly', function () {
            $response = $this->get('/search');
            $response->assertStatus(200);
            $response->assertViewIs('cars.search');
        });

        test('empty search returns all cars', function () {
            $response = $this->get('/search?q=');
            $response->assertStatus(200);
            $response->assertViewHas('cars');
        });
    });

    describe('Security', function () {
        describe('SQL Injection Prevention', function () {
            test('protects against various SQL injection attempts', function () {
                $maliciousQueries = [
                    "' OR '1'='1",
                    "'; DROP TABLE cars; --",
                    "' UNION SELECT * FROM users --",
                    "' OR '1'='1' --",
                    "'; INSERT INTO cars (manufacturer_id, model) VALUES (1, 'HACKED'); --"
                ];

                foreach ($maliciousQueries as $query) {
                    $response = $this->get('/search?q=' . urlencode($query));
                    $response->assertStatus(200);
                    $this->assertDatabaseHas('cars', ['model' => 'Camry']); // Verify database integrity
                }
            });
        });

        describe('XSS Prevention', function () {
            test('escapes potentially malicious content', function () {
                $xssManufacturer = Manufacturer::factory()->create([
                    'name' => '<script>alert("xss")</script>',
                    'country' => 'Test'
                ]);

                $xssCar = Car::factory()->create([
                    'manufacturer_id' => $xssManufacturer->id,
                    'model' => '<img src="x" onerror="alert(\'xss\')">',
                    'year' => '2023-01-01',
                    'colour' => 'Red'
                ]);

                $response = $this->get('/search?q=' . urlencode($xssCar->model));

                $response->assertDontSee('<script>alert("xss")</script>', false);
                $response->assertDontSee('<img src="x" onerror="alert(\'xss\')">', false);
                $response->assertSee(htmlspecialchars($xssCar->model), false);
            });
        });
    });

    describe('Input Validation', function () {
        test('handles special characters correctly', function () {
            $specialChars = ['@', '#', '$', '%', '&', '*', '(', ')', '+', '='];

            foreach ($specialChars as $char) {
                $response = $this->get('/search?q=' . urlencode($char));
                $response->assertStatus(200);
            }
        });

        test('validates input length', function () {
            // Test with exactly 255 characters (maximum allowed)
            $maxQuery = str_repeat('a', 255);
            $response = $this->get('/search?q=' . urlencode($maxQuery));
            $response->assertStatus(200);

            // Test with too long input (should redirect back with validation error)
            $tooLongQuery = str_repeat('a', 256);
            $response = $this->get('/search?q=' . urlencode($tooLongQuery));
            $response->assertStatus(302); // Expect redirect
            $response->assertSessionHasErrors('q'); // Expect validation error
        });
    });

    describe('Performance', function () {
        test('caches search results properly', function () {
            // First search to cache results
            $response1 = $this->get('/search?q=Camry');

            // Modify the database directly
            Car::where('model', 'Camry')->update(['colour' => 'Blue']);

            // Second search should return cached results
            $response2 = $this->get('/search?q=Camry');

            // Both responses should show the original color
            $response1->assertSee('Red');
            $response2->assertSee('Red');
        });

        test('handles concurrent searches properly', function () {
            $searchTerms = ['Camry', 'Toyota', 'Red'];

            foreach ($searchTerms as $term) {
                $response = $this->get('/search?q=' . urlencode($term));
                $response->assertStatus(200);
            }
        });
    });

    describe('Pagination', function () {
        test('validates pagination parameters', function () {
            $invalidPages = ['0', '-1', 'abc', '99999999999999'];

            foreach ($invalidPages as $page) {
                $response = $this->get('/search?page=' . $page);
                $response->assertStatus(200); // Should handle gracefully
            }
        });
    });
});
