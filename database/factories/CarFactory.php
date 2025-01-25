<?php

namespace Database\Factories;

use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Car>
 */
class CarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'manufacturer_id' => Manufacturer::factory(),
            'model' => fake()->randomElement([
                'G-Class',
                'Breeze',
                'LeSabre',
                'Accent',
                'Acclaim',
                'L-Series',
                'A4',
                'Leone',
                'Continental',
                'Bronco',
                'Maxima',
                'F150',
                'Swift',
                '500',
                '3500',
            ]),
            'year' => fake()->numberBetween(1886, date('Y')),
            'colour' => fake()->colorName(),
        ];
    }
}
