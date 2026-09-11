<?php

namespace App\Game\Gems\Progression\Exceptions;

use App\Game\Gems\Progression\Values\GemProgressionBands;
use RuntimeException;

class GemScrollNotEligibleException extends RuntimeException
{
    /**
     * Build the exception for a Character whose personal Gem level is below the Gem Scroll eligibility level.
     *
     * @param int $personalLevel
     * @return self
     */
    public static function forPersonalLevel(int $personalLevel): self
    {
        return new self(
            'Personal Gem level '.$personalLevel.' is below the Gem Scroll eligibility level of '
            .GemProgressionBands::PERSONAL_BASE_CAP_LEVEL.'.'
        );
    }
}
