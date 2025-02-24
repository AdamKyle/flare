<?php

namespace App\Flare\AlchemyItemGenerator\DTO;

class AlchemyItemDTO
{
    /**
     * @var string $type
     */
    private string $type;

    /**
     * @var int|null $skillType
     */
    private ?int $skillType = null;

    /**
     * Sets the alchemy item type.
     *
     * @param string $type
     * @return AlchemyItemDTO
     */
    public function setType(string $type): AlchemyItemDTO
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Sets the skill type associated with the alchemy item.
     *
     * @param int|null $skillType
     * @return AlchemyItemDTO
     */
    public function setSkillType(?int $skillType = null): AlchemyItemDTO
    {
        $this->skillType = $skillType;
        return $this;
    }

    /**
     * Gets the alchemy item type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets the skill type associated with the alchemy item.
     *
     * @return int|null
     */
    public function getSkillType(): ?int
    {
        return $this->skillType;
    }
}
