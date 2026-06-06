<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateDelveAutomation;
use Tests\Traits\CreateFactionLoyaltyAutomation;

class AutomationManagement
{
    use CreateCharacterAutomation,
        CreateDelveAutomation,
        CreateFactionLoyaltyAutomation;

    private Character $character;

    private CharacterFactory $characterFactory;

    private ?CharacterAutomation $characterAutomation = null;

    private ?DelveExploration $delveAutomation = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    public function __construct(Character $character, CharacterFactory $characterFactory)
    {
        $this->character = $character;
        $this->characterFactory = $characterFactory;
    }

    public function assignExplorationAutomation(array $options = []): AutomationManagement
    {
        unset($options['completed_at']);

        $this->characterAutomation = $this->createCharacterAutomation(array_merge([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(2),
            'attack_type' => AttackTypeValue::ATTACK,
        ], $options));

        return $this;
    }

    public function assignDelveAutomation(array $options = []): AutomationManagement
    {
        $this->delveAutomation = $this->createDelveAutomation(array_merge([
            'character_id' => $this->character->id,
        ], $options));

        return $this;
    }

    public function assignFactionLoyaltyAutomation(array $options = []): AutomationManagement
    {
        $this->factionLoyaltyAutomation = $this->createFactionLoyaltyAutomation(array_merge([
            'character_id' => $this->character->id,
        ], $options));

        return $this;
    }

    public function getCharacterAutomation(): ?CharacterAutomation
    {
        return $this->characterAutomation?->refresh();
    }

    public function getDelveAutomation(): ?DelveExploration
    {
        return $this->delveAutomation?->refresh();
    }

    public function getFactionLoyaltyAutomation(): ?FactionLoyaltyAutomation
    {
        return $this->factionLoyaltyAutomation?->refresh();
    }

    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    public function getCharacterFactory(): CharacterFactory
    {
        return $this->characterFactory;
    }
}
