<?php

namespace App\Game\Automation\Contracts;

use App\Game\Automation\Values\AutomatedCraftingResult;

interface AutomatedCraftingLogger
{
    /**
     * Log the automated crafting result.
     */
    public function log(AutomatedCraftingResult $automatedCraftingResult): void;
}
