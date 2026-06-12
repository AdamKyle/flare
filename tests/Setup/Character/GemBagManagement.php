<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use Tests\Traits\CreateGem;

class GemBagManagement
{
    use CreateGem;

    private $character;

    private $characterFactory;

    /**
     * Constructor
     */
    public function __construct(Character $character, ?CharacterFactory $characterFactory = null)
    {
        $this->character = $character;
        $this->characterFactory = $characterFactory;
    }

    /**
     * Get the character factory.
     */
    public function getCharacterFactory(): CharacterFactory
    {
        return $this->characterFactory;
    }

    /**
     * Get the character back.
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Give the player a set of gems
     */
    public function assignGemsToBag(int $amount = 1, int $amountOfGems = 1): GemBagManagement
    {
        for ($i = 1; $i <= $amount; $i++) {
            $gem = $this->createGem();

            for ($gemCount = 1; $gemCount <= $amountOfGems; $gemCount++) {
                $this->character->gemBag->gemSlots()->create([
                    'gem_bag_id' => $this->character->gemBag->id,
                    'gem_id' => $gem->id,
                    'amount' => 1,
                ]);
            }

            $this->character = $this->character->refresh();
        }

        return $this;
    }

    /**
     * Assign a specific gem to the character.
     *
     * @return $this
     */
    public function assignGemToBag(int $gemId, int $amount = 1): GemBagManagement
    {
        for ($gemCount = 1; $gemCount <= $amount; $gemCount++) {
            $this->character->gemBag->gemSlots()->create([
                'gem_bag_id' => $this->character->gemBag->id,
                'gem_id' => $gemId,
                'amount' => 1,
            ]);
        }

        $this->character = $this->character->refresh();

        return $this;
    }
}
