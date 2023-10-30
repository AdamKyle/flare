<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use Tests\Traits\CreateGem;

class GemBagManagement {

    use CreateGem;

    private $character;

    private $characterFactory;

    /**
     * Constructor
     *
     * @param Character $character
     * @param CharacterFactory|null $characterFactory
     */
    public function __construct(Character $character, CharacterFactory $characterFactory = null) {
        $this->character        = $character;
        $this->characterFactory = $characterFactory;
    }

    /**
     * Get the character factory.
     *
     * @return CharacterFactory
     */
    public function getCharacterFactory(): CharacterFactory {
        return $this->characterFactory;
    }

    /**
     * Get the character back.
     *
     * @return Character
     */
    public function getCharacter(): Character {
        return $this->character->refresh();
    }

    /**
     * Give the player a set of gems
     *
     * @param integer $amount
     * @param integer $amountOfGems
     * @return GemBagManagement
     */
    public function assignGemsToBag(int $amount = 1, int $amountOfGems = 1): GemBagManagement {
        for ($i = 1; $i <= $amount; $i++) {
            $this->character->gemBag->gemSlots()->create([
                'gem_bag_id' => $this->character->gemBag->id,
                'gem_id'     => $this->createGem()->id,
                'amount'     => $amountOfGems,
            ]);

            $this->character = $this->character->refresh();
        }

        return $this;
    }
}
