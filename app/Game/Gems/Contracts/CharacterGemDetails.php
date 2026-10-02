<?php

namespace App\Game\Gems\Contracts;

interface CharacterGemDetails
{
    /**
     * Return factual snapshots for the given character-domain Gems.
     *
     * @param array $gemIds
     * @return array
     */
    public function forGemIds(array $gemIds): array;
}
