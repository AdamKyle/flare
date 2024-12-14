<?php

namespace App\Game\Messages\Types\Concerns;

interface BaseMessageType
{
    public function getValue(): string;
}
