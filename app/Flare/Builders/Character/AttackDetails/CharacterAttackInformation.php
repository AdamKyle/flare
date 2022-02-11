<?php

namespace App\Flare\Builders\Character\AttackDetails;

use App\Flare\Builders\Character\ClassDetails\ClassBonuses;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\ItemAffix;
use Illuminate\Support\Collection;

class CharacterAttackInformation {

    use FetchEquipped;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var CharacterInformationBuilder $characterInformationBuilder;
     */
    private $characterInformationBuilder;

    /**
     * @var CharacterDamageInformation $characterDamageInformation
     */
    private $characterDamageInformation;

    /**
     * @var CharacterAffixInformation
     */
    private $characterAffixInformation;

    /**
     * @var Collection $inventory
     */
    private $inventory;

    /**
     * @param CharacterDamageInformation $characterDamageInformation
     * @param CharacterAffixInformation $characterAffixInformation
     */
    public function __construct(CharacterDamageInformation $characterDamageInformation, CharacterAffixInformation $characterAffixInformation) {
        $this->characterDamageInformation = $characterDamageInformation;
        $this->characterAffixInformation  = $characterAffixInformation;
    }

    /**
     * @param Character $character
     * @return CharacterAttackInformation
     */
    public function setCharacter(Character $character): CharacterAttackInformation {
        $this->character = $character;

        $this->characterAffixInformation = $this->characterAffixInformation->setCharacter($character);

        return $this;
    }

    /**
     * Sets the character information builder.
     *
     * @param CharacterInformationBuilder $characterInformationBuilder
     * @return CharacterAttackInformation
     */
    public function setCharacterInformationBuilder(CharacterInformationBuilder $characterInformationBuilder): CharacterAttackInformation {
        $this->characterInformationBuilder = $characterInformationBuilder;

        return $this;
    }

    /**
     * Fetch the damage information instance.
     *
     * @return CharacterDamageInformation
     */
    public function getCharacterDamageInformation(): CharacterDamageInformation {
        return $this->characterDamageInformation;
    }

    /**
     * Fetch the inventory for the character with equipped items.
     *
     * @return Collection
     */
    public function fetchInventory(): Collection
    {
        $slots = $this->fetchEquipped($this->character);

        if (is_null($slots)) {
            return collect([]);
        }

        return $slots;
    }

    /**
     * Calculates the attribute value based on equipped affixes.
     *
     * @param string $attribute
     * @return float
     */
    public function calculateAttributeValue(string $attribute): float {
        $slots = $this->fetchInventory()->filter(function($slot) use($attribute) {
            if (!is_null($slot->item->itemPrefix))  {
                if ($slot->item->itemPrefix->{$attribute} > 0) {
                    return $slot;
                }
            }

            if (!is_null($slot->item->itemSuffix))  {
                if ($slot->item->itemSuffix->{$attribute} > 0) {
                    return $slot;
                }
            }
        });

        $values = [];

        foreach ($slots as $slot) {
            if (!is_null($slot->item->itemPrefix))  {
                $values[] = $slot->item->itemPrefix->{$attribute};
            }

            if (!is_null($slot->item->itemSuffix))  {
                $values[] = $slot->item->itemSuffix->{$attribute};
            }
        }

        return empty($values) ? 0.0 : max($values);
    }

    /**
     * Find the prefix that reduces stats.
     *
     * We take the first one. It makes it easier than trying to figure out
     * which one is better.
     *
     * These cannot stack.
     *
     * @return ItemAffix|null
     */
    public function findPrefixStatReductionAffix(): ?ItemAffix {
        return $this->characterAffixInformation->findPrefixStatReductionAffix();
    }

    /**
     * Fetch all suffix items  that reduce an enemies stats.
     *
     * @return Collection
     */
    public function findSuffixStatReductionAffixes(): Collection {
        return $this->characterAffixInformation->findSuffixStatReductionAffixes();
    }

    /**
     * Do we have any affixes of the applied attribute type?
     *
     * @param string $type
     * @return bool
     */
    public function hasAffixesWithType(string $type): bool {
        return $this->characterAffixInformation->hasAffixesWithType($type);
    }

    /**
     * Get total affix damage.
     *
     * @param bool $canStack
     * @return int
     */
    public function getTotalAffixDamage(bool $canStack = true): int {
        return $this->characterAffixInformation->getTotalAffixDamage($canStack);
    }

    /**
     * Fetch Voidance amount.
     *
     * @param string $type
     * @return float
     */
    public function fetchVoidanceAmount(string $type): float {
        return $this->characterAffixInformation->fetchVoidanceAmount($type);
    }
}
