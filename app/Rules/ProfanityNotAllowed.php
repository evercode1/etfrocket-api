<?php

namespace App\Rules;

use App\Services\Auth\ProfanityFilterService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ProfanityNotAllowed implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((new ProfanityFilterService)->matches($value)) {

            $fail('Name has been taken. Please try another.');

        }

    }
}
