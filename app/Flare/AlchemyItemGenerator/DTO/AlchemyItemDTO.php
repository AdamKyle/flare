<?php

namespace App\Flare\AlchemyItemGenerator\DTO;

class AlchemyItemDTO
{
    private string $type;

    private ?int $skillType = null;

    public function setType(string $type): AlchemyItemDTO
    {
        $this->type = $type;

        return $this;
    }

    public function setSkillType(?int $skillType = null): AlchemyItemDTO
    {
        $this->skillType = $skillType;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSkillType(): ?int
    {
        return $this->skillType;
    }
}
