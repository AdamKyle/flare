<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use App\Flare\Values\RandomAffixDetails;

class RandomAffixGenerator {

    /**
     * @var AffixAttributeBuilder $affixAttributeBuilder
     */
    private AffixAttributeBuilder $affixAttributeBuilder;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @var int $amountPaid
     */
    private int $amountPaid;

    /**
     * @param AffixAttributeBuilder $affixAttributeBuilder
     */
    public function __construct(AffixAttributeBuilder $affixAttributeBuilder) {
        $this->affixAttributeBuilder = $affixAttributeBuilder;
    }

    /**
     * @param Character $character
     * @return RandomAffixGenerator
     */
    public function setCharacter(Character $character): RandomAffixGenerator {
        $this->character = $character;

        return $this;
    }

    /**
     * Sets the paid amount and sets basic details.
     *
     * @param int $amount
     * @return RandomAffixGenerator
     * @throws \Exception
     */
    public function setPaidAmount(int $amount = 0): RandomAffixGenerator {
        $this->amountPaid            = $amount !== 0 ? $amount : RandomAffixDetails::BASIC;
        $details                     = (new RandomAffixDetails($this->amountPaid));
        $this->affixAttributeBuilder = $this->affixAttributeBuilder->setPercentageRange($details->getPercentageRange())
                                                                   ->setCharacterSkills($this->character->skills)
                                                                   ->setDamageRange($details->getDamageRange());

        return $this;
    }

    /**
     * Generate the Affix.
     *
     * @param string $type
     * @return ItemAffix
     */
    public function generateAffix(string $type): ItemAffix {
        $attributes = $this->affixAttributeBuilder->buildAttributes($type, $this->amountPaid);

        $foundMatchingPrefix = ItemAffix::where($attributes)->first();

        if (!is_null($foundMatchingPrefix)) {
            return $foundMatchingPrefix;
        }

        $attributes['affix_type'] = ItemAffixType::RANDOMLY_GENERATED;

        return ItemAffix::create($attributes);
    }
}
