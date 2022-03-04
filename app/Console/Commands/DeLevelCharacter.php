<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Battle\Values\MaxLevel;
use App\Game\Core\Services\CharacterService;
use Illuminate\Console\Command;

/**
 * @codeCoverageIgnore
 */
class DeLevelCharacter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'de-level:characters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'de-levels characters back to max.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(CharacterService $characterService)
    {
        $config   = MaxLevelConfiguration::first();
        $maxLevel = MaxLevel::MAX_LEVEL;

        if (!is_null($config)) {
            $maxLevel = $config->max_level;
        }

        Character::where('level', '>', $maxLevel)->chunkById(250, function($characters) use($maxLevel) {
           foreach ($characters as $character) {
               $diffInLevels = $character->level - $maxLevel;

               for ($i = $diffInLevels; $i > 0; $i--) {
                   $character->update($this->createValueObject($character));
               }

               resolve(BuildCharacterAttackTypes::class)->buildCache($character);
           }
        });
    }

    /**
     * Create the level up value object.
     *
     * Increases core stats.
     *
     * @param Character $character
     * @return array
     */
    protected function createValueObject(Character $character) {
        return [
            'level' => $character->level - 1,
            'xp'    => 0,
            'str'   => $this->addValue($character, 'str'),
            'dur'   => $this->addValue($character, 'dur'),
            'dex'   => $this->addValue($character, 'dex'),
            'chr'   => $this->addValue($character, 'chr'),
            'int'   => $this->addvalue($character, 'int'),
            'agi'   => $this->addvalue($character, 'agi'),
            'focus' => $this->addvalue($character, 'focus'),
        ];
    }

    /**
     * Add the new value to the character stat.
     *
     * Regular stats get +1 and the damage stat gets a +2
     *
     * @param Character $character
     * @param string $currentStat
     * @return int
     */
    private function addValue(Character $character, string $currenStat): int {
        if ($character->damage_stat === $currenStat) {
            return $character->{$currenStat} -= 2;
        }

        return $character->{$currenStat} -= 1;
    }
}
