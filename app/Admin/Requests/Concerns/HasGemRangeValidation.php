<?php

namespace App\Admin\Requests\Concerns;

use App\Game\Gems\Values\GemRangeNormalizer;
use Closure;

trait HasGemRangeValidation
{
    /**
     * Return every range field managed by the implementing Gem form.
     */
    abstract protected function rangeFields(): array;

    /**
     * Build the shared Gem range-field validation rule.
     */
    protected function rangeRule(): array
    {
        return [
            'nullable',
            'string',
            'max:255',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (GemRangeNormalizer::isAbsent($value)) {
                    return;
                }

                if (! GemRangeNormalizer::isValidFormat($value)) {
                    $fail('The range must contain two nonnegative numeric values separated by a hyphen.');

                    return;
                }

                if (! GemRangeNormalizer::isOrdered($value)) {
                    $fail('The range minimum must be less than or equal to the maximum.');
                }
            },
        ];
    }

    /**
     * Return the validated data with every range field normalized: an absent
     * (blank or all-zero) value becomes null so it is never persisted as text.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();

        foreach ($this->rangeFields() as $rangeField) {
            if (array_key_exists($rangeField, $validated)) {
                $validated[$rangeField] = GemRangeNormalizer::normalize($validated[$rangeField]);
            }
        }

        if ($key !== null) {
            return data_get($validated, $key, $default);
        }

        return $validated;
    }
}
