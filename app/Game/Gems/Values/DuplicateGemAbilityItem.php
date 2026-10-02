<?php

namespace App\Game\Gems\Values;

use App\Flare\Models\Item;
use App\Game\Core\Items\Values\ItemUniqueness;

class DuplicateGemAbilityItem
{
    /**
     * @param int $itemId
     * @param string $name
     * @param int $affixCount
     * @param bool $isUnique
     * @param bool $isMythic
     * @param bool $isCosmic
     * @param int|null $holyStacksApplied
     * @param int|null $holyLevel
     * @param string $type
     * @param bool $usable
     * @param bool $damagesKingdoms
     * @param GemAbilityOwnershipSource $source
     * @param int|null $setId
     * @param string|null $setName
     */
    public function __construct(
        public readonly int $itemId,
        public readonly string $name,
        public readonly int $affixCount,
        public readonly bool $isUnique,
        public readonly bool $isMythic,
        public readonly bool $isCosmic,
        public readonly ?int $holyStacksApplied,
        public readonly ?int $holyLevel,
        public readonly string $type,
        public readonly bool $usable,
        public readonly bool $damagesKingdoms,
        public readonly GemAbilityOwnershipSource $source,
        public readonly ?int $setId,
        public readonly ?string $setName,
    ) {}

    /**
     * Build the affected-Item snapshot used by duplicate Gem Ability warnings.
     *
     * @param OwnedGemItem $ownedGemItem
     * @return DuplicateGemAbilityItem
     */
    public static function fromOwnedGemItem(OwnedGemItem $ownedGemItem): DuplicateGemAbilityItem
    {
        $item = $ownedGemItem->item;

        return new DuplicateGemAbilityItem(
            $item->id,
            $item->affix_name,
            $item->affix_count,
            ItemUniqueness::fromItem($item)->isUnique(),
            $item->is_mythic,
            $item->is_cosmic,
            $item->holy_stacks_applied,
            $item->holy_level,
            $item->type,
            $item->usable,
            $item->damages_kingdoms,
            $ownedGemItem->source,
            $ownedGemItem->setId,
            $ownedGemItem->setName,
        );
    }

    /**
     * Serialize the affected Item into the warning payload used by item-color presentation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'name' => $this->name,
            'affix_count' => $this->affixCount,
            'is_unique' => $this->isUnique,
            'is_mythic' => $this->isMythic,
            'is_cosmic' => $this->isCosmic,
            'holy_stacks_applied' => $this->holyStacksApplied,
            'holy_level' => $this->holyLevel,
            'type' => $this->type,
            'usable' => $this->usable,
            'damages_kingdoms' => $this->damagesKingdoms,
            'source' => $this->source->value,
            'set_id' => $this->setId,
            'set_name' => $this->setName,
        ];
    }
}
