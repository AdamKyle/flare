<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use Tests\Traits\CreateGem;

class GemBagManagement
{
    use CreateGem;

    public function __construct(private Character $character, private readonly ?CharacterFactory $characterFactory = null) {}

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
        $gemBag = $this->character->gemBag;

        $gemSlots = collect();

        for ($i = 1; $i <= $amount; $i++) {
            $gemSlots->push([
                'gem_bag_id' => $gemBag->id,
                'gem_id' => $this->createGem()->id,
                'amount' => $amountOfGems,
            ]);
        }

        $gemSlots->chunk(100)->each(function ($chunk) use ($gemBag) {
            $gemBag->gemSlots()->insert($chunk->all());
        });

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Assign a specific gem to the character.
     *
     * @return $this
     */
    public function assignGemToBag(int $gemId, int $amount = 1): GemBagManagement
    {
        $this->character->gemBag->gemSlots()->create([
            'gem_bag_id' => $this->character->gemBag->id,
            'gem_id' => $gemId,
            'amount' => $amount,
        ]);

        $this->character = $this->character->refresh();

        return $this;
    }
}
