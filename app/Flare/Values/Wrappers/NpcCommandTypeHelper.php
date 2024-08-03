<?php

namespace App\Flare\Values\Wrappers;

use App\Flare\Values\NpcCommandTypes;

class NpcCommandTypeHelper
{
    /**
     * Wrapper to get the NPC Command Type name.
     *
     * @throws \Exception
     */
    public static function statusType(int $type): string
    {
        return (new NpcCommandTypes($type))->getNamedValue();
    }
}
