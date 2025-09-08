<?php

namespace App\Flare\Items\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use App\Flare\Values\RandomAffixDetails;

class RandomAffixGenerator
{
    private AffixAttributeBuilder $affixAttributeBuilder;

    private Character $character;

    private int $amountPaid;

    public function __construct(AffixAttributeBuilder $affixAttributeBuilder)
    {
        $this->affixAttributeBuilder = $affixAttributeBuilder;
    }

    public function setCharacter(Character $character): RandomAffixGenerator
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Sets the paid amount and sets basic details.
     *
     * @throws \Exception
     */
    public function setPaidAmount(int $amount = 0): RandomAffixGenerator
    {
        $this->amountPaid = $amount !== 0 ? $amount : RandomAffixDetails::LEGENDARY;
        $details = (new RandomAffixDetails($this->amountPaid));

        $this->affixAttributeBuilder = $this->affixAttributeBuilder->setPercentageRange($details->getPercentageRange())
            ->setCharacterSkills($this->character->skills)
            ->setDamageRange($details->getDamageRange());

        return $this;
    }

    /**
     * Generate the Affix.
     */
    public function generateAffix(string $type): ItemAffix
    {
        $attributes = $this->affixAttributeBuilder->buildAttributes($type, $this->amountPaid);

        $foundMatchingPrefix = $this->fetchMatchingAffix($attributes);

        if (! is_null($foundMatchingPrefix)) {
            return $foundMatchingPrefix;
        }

        $attributes['affix_type'] = ItemAffixType::RANDOMLY_GENERATED;

        return ItemAffix::create($attributes);
    }

    /**
     * find a possible matching affix.
     *
     * Note: This is a wrapper so I can mock this in tests.
     */
    protected function fetchMatchingAffix(array $attributes): ?ItemAffix
    {
        return ItemAffix::where($attributes)->first();
    }
}
