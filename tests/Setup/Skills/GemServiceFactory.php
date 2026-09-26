<?php

namespace Tests\Setup\Skills;

use App\Game\Character\CharacterInventory\Transformers\CharacterGemSlotsTransformer;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\PhpRandomNumberGenerator;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Transformers\GemTransformer;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Skills\Services\GemService;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;

class GemServiceFactory
{
    /**
     * Build a real GemService, optionally replacing its random chance or gem building boundaries.
     *
     * @param ?ChanceCalculator $chanceCalculator
     * @param ?GemBuilder $gemBuilder
     */
    public function build(?ChanceCalculator $chanceCalculator = null, ?GemBuilder $gemBuilder = null): GemService
    {
        $randomNumberGenerator = new PhpRandomNumberGenerator;

        return new GemService(
            $gemBuilder ?? new GemBuilder($randomNumberGenerator),
            $chanceCalculator ?? new ChanceCalculator($randomNumberGenerator),
            new GemTransformer,
            new ServerMessageBuilder,
            new CharacterGemSlotsTransformer,
            new SkillBonusService(new SkillBonusContextService),
        );
    }
}
