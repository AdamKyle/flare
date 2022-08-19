<?php

namespace App\Console\Commands;

use App\Flare\Builders\AffixAttributeBuilder;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\RandomAffixDetails;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class ReBalanceAffixes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 're-balance:affixes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Balances the affixes';

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
    public function handle(AffixAttributeBuilder $affixAttributeBuilder) {
        $this->rebalanceUniques($affixAttributeBuilder);
    }

    public function rebalanceUniques(AffixAttributeBuilder $affixAttributeBuilder) {
        foreach (ItemAffix::where('cost', '=', RandomAffixDetails::BASIC)->where('randomly_generated', true)->get() as $affix) {
            $this->processItemsWithUnique($affix, $affixAttributeBuilder);
        }

        foreach (ItemAffix::where('cost', '=', RandomAffixDetails::MEDIUM)->where('randomly_generated', true)->get() as $affix) {
            $this->processItemsWithUnique($affix, $affixAttributeBuilder);
        }

        foreach (ItemAffix::where('cost', '=', RandomAffixDetails::LEGENDARY)->where('randomly_generated', true)->get() as $affix) {
            $this->processItemsWithUnique($affix, $affixAttributeBuilder);
        }
    }

    protected function processItemsWithUnique(ItemAffix $affix, AffixAttributeBuilder $affixAttributeBuilder) {
        $amountPaid             = new RandomAffixDetails($affix->cost);

        $affixAttributeBuilder =  $affixAttributeBuilder->setPercentageRange($amountPaid->getPercentageRange())
            ->setDamageRange($amountPaid->getDamageRange());

        $attributes = $affixAttributeBuilder->buildAttributes($affix->type, $affix->cost, true);

        unset($attributes['name']);

        $affix->update($attributes);
    }
}
