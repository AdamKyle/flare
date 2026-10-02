<?php

namespace Tests\Setup\Skills;

use App\Game\Character\CharacterInventory\Transformers\CharacterGemSlotsTransformer;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\PhpRandomNumberGenerator;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Services\CharacterGemDetailsService;
use App\Game\Gems\Services\CharacterGemRollService;
use App\Game\Gems\Transformers\CharacterGemTransformer;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Skills\Services\GemService;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;

class GemServiceFactory
{
    /**
     * Build a real GemService, optionally replacing its random chance or gem building boundaries.
     *
     * @param ChanceCalculator|null $chanceCalculator
     * @param GemBuilder|null $gemBuilder
     * @return GemService
     */
    public function build(?ChanceCalculator $chanceCalculator = null, ?GemBuilder $gemBuilder = null): GemService
    {
        $randomNumberGenerator = new PhpRandomNumberGenerator;
        $characterGemRollService = new CharacterGemRollService(
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
        );

        return new GemService(
            $gemBuilder ?? new GemBuilder(
                $randomNumberGenerator,
                $characterGemRollService,
            ),
            $chanceCalculator ?? new ChanceCalculator($randomNumberGenerator),
            new CharacterGemDetailsService,
            new ServerMessageBuilder,
            new CharacterGemSlotsTransformer(new CharacterGemTransformer),
            new SkillBonusService(new SkillBonusContextService),
            $characterGemRollService,
        );
    }
}
