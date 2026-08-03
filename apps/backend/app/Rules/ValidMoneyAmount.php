<?php

namespace App\Rules;

use App\Support\Money\MoneyParser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class ValidMoneyAmount implements ValidationRule
{
    public function __construct(
        protected bool $mustBeGreaterThanZero = true
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            $fail('The amount field is required.');

            return;
        }

        try {
            $minorUnits = MoneyParser::toMinorUnits((string) $value);

            if ($this->mustBeGreaterThanZero && $minorUnits <= 0) {
                $fail('The amount must be greater than ₹0.00.');
            }
        } catch (InvalidArgumentException $e) {
            $fail($e->getMessage());
        }
    }
}
