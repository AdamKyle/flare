<?php

namespace Tests\Unit\Flare\Items\Enricher;

use App\Flare\Items\Enricher\EquippableEnricher;
use App\Flare\Models\HolyStack;
use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class EquippableEnricherTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateItemAffix;

    private ?EquippableEnricher $enricher = null;
    private ?Item $item = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->item = $this->createItem([
            'type' => 'weapon',
        ]);

        HolyStack::create([
            'item_id' => $this->item->id,
            'devouring_darkness_bonus' => 0.1,
            'stat_increase_bonus' => 0.0,
        ]);

        $this->enricher = $this->app->make(EquippableEnricher::class);
    }

    public function tearDown(): void
    {
        $this->item = null;
        $this->enricher = null;

        parent::tearDown();
    }

    public function testEnrichesBaseItemStats(): void
    {
        $this->item->update([
            'base_damage' => 100,
            'base_healing' => 50,
            'base_ac' => 25,
            'base_damage_mod' => 0.10,
            'base_healing_mod' => 0.20,
            'base_ac_mod' => 0.30,
        ]);

        $enriched = $this->enricher->enrich($this->item);

        $this->assertEquals(110, $enriched->total_damage);
        $this->assertEquals(60, $enriched->total_healing);
        $this->assertEquals(33, $enriched->total_defence);
    }

    public function testIncludesAffixModifiersInEnrichment(): void
    {
        $prefix = $this->createItemAffix([
            'base_damage_mod' => 0.10,
            'devouring_light' => 0.05,
        ]);

        $suffix = $this->createItemAffix([
            'base_damage_mod' => 0.15,
            'devouring_light' => 0.10,
        ]);

        $this->item->update([
            'base_damage' => 100,
            'base_damage_mod' => 0.05,
            'devouring_light' => 0.05,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
        ]);

        $enriched = $this->enricher->enrich($this->item->fresh());

        $this->assertEquals(130, $enriched->total_damage);
        $this->assertEquals(0.20, $enriched->devouring_light);
    }

    public function testCalculatesDevouringDarknessFromBaseAndHoly(): void
    {
        $this->item->update([
            'devouring_darkness' => 0.05,
        ]);

        $enriched = $this->enricher->enrich($this->item);

        $this->assertEqualsWithDelta(0.15, $enriched->devouring_darkness, 0.00001);
    }

    public function testCategorizedDamageFromAffixes(): void
    {
        $prefix = $this->createItemAffix([
            'damage_amount' => 10,
            'damage_can_stack' => true,
            'irresistible_damage' => true,
        ]);

        $suffix = $this->createItemAffix([
            'damage_amount' => 5,
            'damage_can_stack' => false,
            'irresistible_damage' => true,
        ]);

        $this->item->update([
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
        ]);

        $enriched = $this->enricher->enrich($this->item->fresh());

        $this->assertEquals(10, $enriched->total_stackable_affix_damage);
        $this->assertEquals(5, $enriched->total_non_stacking_affix_damage);
        $this->assertEquals(15, $enriched->total_irresistible_affix_damage);
    }

    public function testEnrichesDamageStatIfGiven(): void
    {
        $this->item->update([
            'str_mod' => 0.2,
        ]);

        $enriched = $this->enricher->enrich($this->item, 'str');

        $this->assertEquals(0.2, $enriched->total_base_damage_stat);
    }

    public function testBuildsSkillSummaryFromAffixesAndItem(): void
    {
        $prefix = $this->createItemAffix([
            'skill_name' => 'Alchemy',
            'skill_training_bonus' => 0.10,
            'skill_bonus' => 0.05,
        ]);

        $suffix = $this->createItemAffix([
            'skill_name' => 'Crafting',
            'skill_training_bonus' => 0.15,
            'skill_bonus' => 0.10,
        ]);

        $this->item->update([
            'skill_name' => 'Accuracy',
            'skill_training_bonus' => 0.2,
            'skill_bonus' => 0.1,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
        ]);

        $enriched = $this->enricher->enrich($this->item);

        $this->assertCount(3, $enriched->skill_summary);
    }

    /**
     * NEW: Cover the suffix branch in calculateTotalStackableDamage().
     * Only a suffix provides stackable damage.
     */
    public function testStackableDamageFromSuffixOnly(): void
    {
        $suffix = $this->createItemAffix([
            'damage_amount' => 7,
            'damage_can_stack' => true,
            'irresistible_damage' => false,
        ]);

        $this->item->update([
            'item_suffix_id' => $suffix->id,
            'item_prefix_id' => null,
        ]);

        $enriched = $this->enricher->enrich($this->item->fresh());

        $this->assertEquals(7.0, $enriched->total_stackable_affix_damage);
        $this->assertEquals(0.0, $enriched->total_non_stacking_affix_damage);
        $this->assertEquals(0.0, $enriched->total_irresistible_affix_damage);
    }

    public function testNonStackingDamageFromPrefixOnly(): void
    {
        $prefix = $this->createItemAffix([
            'damage_amount' => 9,
            'damage_can_stack' => false,
            'irresistible_damage' => false,
        ]);

        $this->item->update([
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => null,
        ]);

        $enriched = $this->enricher->enrich($this->item->fresh());

        $this->assertEquals(0.0, $enriched->total_stackable_affix_damage);
        $this->assertEquals(9.0, $enriched->total_non_stacking_affix_damage);
        $this->assertEquals(0.0, $enriched->total_irresistible_affix_damage);
    }
}
