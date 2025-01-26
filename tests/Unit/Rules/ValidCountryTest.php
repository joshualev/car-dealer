<?php

use App\Rules\ValidCountry;

describe('Country Validation', function () {
    $validate = function ($input) {
        $rule = new ValidCountry();
        $fails = false;
        $rule->validate('country', $input, function () use (&$fails) {
            $fails = true;
        });
        return ['passes' => !$fails, 'normalized' => $rule->getNormalized()];
    };

    // Test dataset normalization
    $testCases = [
        'German'          => 'Germany',
        'Nited States'    => 'United States',
        'Japanese'        => 'Japan',
        'South Korean'    => 'South Korea',
        'Italian'         => 'Italy',
        'Swedish'         => 'Sweden',
        'United Kingdom'  => 'United Kingdom' // Exact match
    ];

    foreach ($testCases as $input => $expected) {
        it("normalizes $input to $expected", function () use ($validate, $input, $expected) {
            expect($validate($input))->toMatchArray([
                'passes' => true,
                'normalized' => $expected
            ]);
        });
    }

    it('rejects invalid inputs', function () use ($validate) {
        expect($validate('XYZ'))->toMatchArray([
            'passes' => false,
            'normalized' => null
        ]);
    });
});
