<?php

namespace App\Game\Gems\Values;

use App\Flare\Models\CharacterGemModifier;
use App\Flare\Models\Gem;

class CharacterGemSnapshot
{
    /**
     * @param int $id
     * @param string $name
     * @param int $tier
     * @param string $domain
     * @param array $modifiers
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $tier,
        public readonly string $domain,
        public readonly array $modifiers,
    ) {}

    /**
     * Build the factual snapshot of a character-domain Gem and its rolled modifiers.
     *
     * @param Gem $gem
     * @return CharacterGemSnapshot
     */
    public static function fromGem(Gem $gem): CharacterGemSnapshot
    {
        $gem->loadMissing('characterModifiers.gameGemAbility');

        return new CharacterGemSnapshot(
            $gem->id,
            $gem->name,
            $gem->tier,
            $gem->domain,
            $gem->characterModifiers
                ->map(fn (CharacterGemModifier $modifier): CharacterGemModifierSnapshot => CharacterGemModifierSnapshot::fromModifier($modifier))
                ->values()
                ->all(),
        );
    }

    /**
     * Serialize the Gem snapshot into its API payload.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tier' => $this->tier,
            'domain' => $this->domain,
            'modifiers' => array_map(
                fn (CharacterGemModifierSnapshot $modifier): array => $modifier->toArray(),
                $this->modifiers,
            ),
        ];
    }
}
