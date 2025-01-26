<?php

namespace App\Rules;

use App\Enums\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ValidCountry implements ValidationRule
{
    private ?string $normalized = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $search = Str::lower(trim($value));
        $this->normalized = $this->findMatch($search);

        if (!$this->normalized) {
            $fail('The :attribute must be a valid country name.');
        }
    }

    public function getNormalized(): ?string
    {
        return $this->normalized;
    }

    private function findMatch(string $term): ?string
    {
        // Exact match check
        foreach (Country::cases() as $country) {
            if (Str::lower($country->value) === $term) {
                return $country->value;
            }
        }

        // Suffix removal and partial match
        $modifiedTerm = $this->removeSuffixes($term);
        if (strlen($modifiedTerm) >= 3) {
            foreach (Country::cases() as $country) {
                $countryName = Str::lower($country->value);
                if (Str::contains($countryName, $modifiedTerm)) {
                    return $country->value;
                }
            }
        }

        // Final partial match attempt with original term
        if (strlen($term) >= 3) {
            foreach (Country::cases() as $country) {
                $countryName = Str::lower($country->value);
                if (Str::contains($countryName, $term)) {
                    return $country->value;
                }
            }
        }

        return null;
    }

    private function removeSuffixes(string $term): string
    {
        return preg_replace([
            '/ese$/',    // Japanese → Japan
            '/ian$/',    // Italian → Italy
            '/ish$/',    // Swedish → Sweden
            '/n$/',      // Korean → Korea
            '/man$/'     // German → Germany
        ], '', $term);
    }
}
