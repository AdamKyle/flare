<?php

namespace App\Flare\AffixGenerator\DTO;

use App\Flare\Models\GameSkill;

class AffixGeneratorDTO {

    private string $prefixOrSuffix;

    private string $affixType;

    private ?string $skillName = null;

    private array $attributes;

    private bool $isIrresistible  = false;

    private bool $doesDamageStack = false;

    public function setIsDamageIrresistible(bool $isIrresistible) {
        $this->isIrresistible = $isIrresistible;
    }

    public function setDoesDamageStatck(bool $doesDamageStack) {
        $this->doesDamageStack = $doesDamageStack;
    }

    public function setPrefixOrSuffix(string $prefixOrSuffix) {
        $this->prefixOrSuffix = $prefixOrSuffix;
    }

    public function setSkillName(string $skillName) {
        $this->skillName = $skillName;
    }

    public function setAttributes(array $attributes) {
        $this->attributes = $attributes;
    }

    public function setAffixType(int $type) {
        $this->affixType = $type;
    }

    public function getPrefixOrSuffix(): string {
        return $this->prefixOrSuffix;
    }

    public function getType(): string {
        return $this->affixType;
    }

    public function getSkillName(): ?string {
        return $this->skillName;
    }

    public function getAttributes(): array {
        return $this->attributes;
    }

    public function getIsDamageIrresistible(): bool {
        return $this->isIrresistible;
    }

    public function getDoesDamageStatck(): bool {
        return $this->doesDamageStack;
    }
}
