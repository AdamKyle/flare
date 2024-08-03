<?php

namespace App\Flare\AffixGenerator\Console\Commands;

use App\Flare\AffixGenerator\DTO\AffixGeneratorDTO;
use App\Flare\AffixGenerator\Generator\GenerateAffixes;
use App\Flare\AffixGenerator\Values\AffixGeneratorTypes;
use App\Flare\Models\GameSkill;
use App\Flare\Values\ItemAffixType;
use Illuminate\Console\Command;

class MassGenerateAffixes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:affixes {amount=25}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Can generate a set of enchantments based on user supplied input.';

    /**
     * Execute the console command.
     */
    public function handle(AffixGeneratorDTO $affixGeneratorDTO, GenerateAffixes $generateAffixes)
    {
        $this->line('Hello and welcome. I will guide you through generating Affixes in mass. Please note all names and descriptions are randomly generated.');
        $this->line('For this reason it is suggested you export the affixes you generate and rename the names and update descriptions.');
        $this->newLine();

        $prefixOrSuffix = $this->prefixOrSuffix();

        $affixGeneratorDTO->setPrefixOrSuffix($prefixOrSuffix);

        $skill = $this->skillForAffixes();

        if (! is_null($skill)) {
            $affixGeneratorDTO->setSkillName($skill);
        }

        $affixGeneratorDTO->setAffixType($this->affixType());

        $attributes = $this->selectAttributesForAffixes();

        if (in_array('damage', $attributes)) {
            $this->isDamageIrresistable($affixGeneratorDTO);

            $this->isDamageStackable($affixGeneratorDTO);
        }

        $affixGeneratorDTO->setAttributes($attributes);

        $sizeLimit = intval($this->argument('amount'));

        $generateAffixes->generate($affixGeneratorDTO, $sizeLimit);
    }

    protected function isDamageIrresistable(AffixGeneratorDTO $affixGeneratorDTO)
    {
        $choice = $this->choice('You selected damage as one of the attributes, would you like to make the damage irresistable?', [
            'Y', 'N',
        ]);

        if ($choice === 'Y') {
            $affixGeneratorDTO->setIsDamageIrresistible(true);
        } else {
            $affixGeneratorDTO->setIsDamageIrresistible(false);
        }
    }

    protected function isDamageStackable(AffixGeneratorDTO $affixGeneratorDTO)
    {
        $choice = $this->choice('You selected damage as one of the attributes, would you like to make the damage stack?', [
            'Y', 'N',
        ]);

        if ($choice === 'Y') {
            $affixGeneratorDTO->setDoesDamageStatck(true);
        } else {
            $affixGeneratorDTO->setDoesDamageStatck(false);
        }
    }

    /**
     * What type of affix are we generating
     */
    protected function prefixOrSuffix(): string
    {
        return $this->choice('What type do these affixes have?', [
            'prefix', 'suffix',
        ]);
    }

    /**
     * What type of affix are we generating
     */
    protected function affixType(): int
    {
        $value = $this->choice('What type do these affixes have?', ItemAffixType::$dropDownValues);

        return ItemAffixType::convertNameToType($value);
    }

    /**
     * Does the batch of affixes effect a skill?
     */
    protected function skillForAffixes(): ?string
    {
        $effects = $this->choice('Do these affixes effect skills?', [
            'Y', 'N',
        ]);

        if ($effects === 'Y') {
            $skill = $this->choice('Please select a skill', GameSkill::pluck('name')->toArray());

            return $skill;
        }

        return null;
    }

    /**
     * Select attributes for the affixes
     */
    protected function selectAttributesForAffixes(): array
    {
        $this->newLine();
        $this->line('Please select a set of attributes you want for these affixes. Keep in mind that if you choose any of the stat reducting attributes');
        $this->line('then the logic states: ');
        $this->line('The logic states that prefixes can reduce all stats and do not stack, while suffixes can reduce individual stats and do stack.');
        $this->newLine();

        $this->line('Note: To select multiple enter the numbers as such: 0,1,2,3 ...');

        return $this->choice('Select one or more attributes for this set of affixes', AffixGeneratorTypes::getValuesForCommandSelection(), null, null, true);
    }
}
