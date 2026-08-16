<?php

namespace App\Game\Automation\FactionLoyalty\Contracts;

use App\Game\Automation\FactionLoyalty\Values\AutomatedCraftingResult;

interface AutomatedCraftingLogger
{
    /**
     * Log the automated crafting result.
     *
     * @param  AutomatedCraftingResult  $automatedCraftingResult  The crafting result to log.
     * @return void This method does not return a value.
     */
    public function log(AutomatedCraftingResult $automatedCraftingResult): void;
}
