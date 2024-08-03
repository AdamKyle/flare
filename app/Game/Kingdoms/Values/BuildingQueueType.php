<?php

namespace App\Game\Kingdoms\Values;

class BuildingQueueType
{
    const UPGRADE = 0;

    const REPAIR = 1;

    protected static $values = [
        self::UPGRADE => self::UPGRADE,
        self::REPAIR => self::REPAIR,
    ];

    private int $type;

    public function __construct(string $type)
    {
        if (! in_array($type, self::$values)) {
            throw new \Exception($type.' does not exist.');
        }

        $this->type = $type;
    }

    public function getNameOfType(): string
    {
        return match ($this->type) {
            self::UPGRADE => 'upgrading',
            self::REPAIR => 'repairing'
        };
    }

    public function isUpgrading(): bool
    {
        return $this->type === self::UPGRADE;
    }

    public function isRepairing(): bool
    {
        return $this->type === self::REPAIR;
    }
}
