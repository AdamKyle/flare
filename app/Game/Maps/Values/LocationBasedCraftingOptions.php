<?php

namespace App\Game\Maps\Values;

use App\Flare\Models\Character;
use App\Game\Core\Items\Values\ItemEffectType;

class LocationBasedCraftingOptions
{
    public function __construct(
        public readonly bool $canUseWorkBench,
        public readonly bool $canUseQueenOfHearts,
        public readonly bool $canAccessLabyrinthOracle,
        public readonly bool $canAccessSeerCamp,
    ) {}

    public static function fromCharacter(Character $character): self
    {
        return new self(
            $character->map?->gameMap?->mapType()->isPurgatory() ?? false,
            self::resolveCanUseQueenOfHearts($character),
            $character->map?->gameMap?->mapType()->isLabyrinth() ?? false,
            $character->map?->gameMap?->mapType()->isPurgatory() ?? false,
        );
    }

    public function toCharacterSheetArray(): array
    {
        return [
            'can_use_work_bench' => $this->canUseWorkBench,
            'can_access_queen' => $this->canUseQueenOfHearts,
            'can_access_labyrinth_oracle' => $this->canAccessLabyrinthOracle,
            'can_access_seer_camp' => $this->canAccessSeerCamp,
        ];
    }

    public function toBroadcastArray(): array
    {
        return [
            'canUseWorkBench' => $this->canUseWorkBench,
            'canUseQueenOfHearts' => $this->canUseQueenOfHearts,
            'canAccessLabyrinthOracle' => $this->canAccessLabyrinthOracle,
            'canAccessSeerCamp' => $this->canAccessSeerCamp,
        ];
    }

    private static function resolveCanUseQueenOfHearts(Character $character): bool
    {
        $hasQueenOfHeartsItem = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectType::QUEEN_OF_HEARTS->value;
        })->isNotEmpty();

        return $hasQueenOfHeartsItem && ($character->map?->gameMap?->mapType()->isHell() ?? false);
    }
}
