<?php

namespace App\Flare\AffixGenerator\DTO;

use App\Flare\Models\GameSkill;

class AffixGeneratorDTO {

    private string $affixType;

    private string $skillName;

    private array $attributes;

    public function setAffixType(string $type) {
        $this->affixType = $type;
    }

    public function setSkillName(string $skillName) {
        $this->skillName = $skillName;
    }

    public function setAttributes(array $attributes) {
        $this->attributes = $attributes;
    }

    public function getType(): string {
        return $this->affixType;
    }

    public function getSkillName(): string {
        return $this->skillName;
    }

    public function getAttributes(): array {
        return $this->attributes;
    }
}