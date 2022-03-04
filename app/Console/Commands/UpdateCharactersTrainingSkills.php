<?php

namespace App\Console\Commands;

use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Jobs\UpdateCharacterSkillsJob;
use App\Game\Skills\Services\SkillService;
use Illuminate\Console\Command;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

/**
 * @codeCoverageIgnore
 */
class UpdateCharactersTrainingSkills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:character-training-skills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update character training skills';

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
    public function handle(SkillService $skillService)
    {
        Character::chunkById(50, function($characters) use($skillService) {
            $gameSkillIds = GameSkill::where('can_train', true)->pluck('id')->toArray();

            foreach ($characters as $character) {
                UpdateCharacterSkillsJob::dispatch($character, $gameSkillIds)->onConnection('long_running');
            }
        });
    }
}
