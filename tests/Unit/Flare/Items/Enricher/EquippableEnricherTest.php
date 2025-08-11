<?php

namespace Tests\Unit\Flare\Items\Enricher;

use App\Flare\Items\Enricher\EquippableEnricher;
use App\Flare\Items\Enricher\Manifest\AutoManifest;
use App\Flare\Items\Enricher\Manifest\EquippableManifest;
use App\Flare\Items\Enricher\Manifest\Values\ManifestSchemaId;
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

    public function testEnrichMethodHasAutoManifestAndResolvesEquippableSchema(): void
    {
        $ref   = new \ReflectionMethod(EquippableEnricher::class, 'enrich');
        $attrs = $ref->getAttributes(AutoManifest::class);

        $this->assertCount(1, $attrs, 'Expected exactly one AutoManifest attribute on enrich().');

        /** @var AutoManifest $meta */
        $meta = $attrs[0]->newInstance();

        // Enum is correct:
        $this->assertSame(
            ManifestSchemaId::EQUIPPABLE,
            $meta->schema
        );

        // Resolve concrete schema:
        $schema = $meta->schema->schema();

        $this->assertSame(
            EquippableManifest::class,
            $schema::class
        );

        $this->assertContains('/^total_.+$/', $schema->includes());
        $this->assertSame('totals.damage', $schema->map('total_damage'));
    }

    public function testSchemaMapsEnrichedItemIntoDotPaths(): void
    {
        // Arrange: make sure enrichment actually sets several props.
        $this->item->update([
            'base_damage'      => 100,
            'base_healing'     => 50,
            'base_ac'          => 25,
            'base_damage_mod'  => 0.10,
            'base_healing_mod' => 0.20,
            'base_ac_mod'      => 0.30,
            'devouring_light'  => 0.05,
        ]);

        // Enrich (existing tests already assert the numbers; here we care about mapping)
        $enriched = $this->enricher->enrich($this->item->fresh());

        // Resolve the schema via the attribute on the method:
        $ref   = new \ReflectionMethod(\App\Flare\Items\Enricher\EquippableEnricher::class, 'enrich');

        /** @var AutoManifest $meta */
        $meta  = $ref->getAttributes(AutoManifest::class)[0]->newInstance();
        $schema = $meta->schema->schema();

        // Build a minimal "mapped bag" like the builder would (only for a few fields).
        $propsToMap = [
            'total_damage',
            'total_healing',
            'total_defence',
            'base_damage_mod',
            'devouring_light',
        ];

        $bag = [];
        foreach ($propsToMap as $prop) {
            $path = $schema->map($prop);
            $this->assertNotNull($path, "Schema should map {$prop} to a path.");

            $this->setDot($bag, $path, $enriched->{$prop});
        }

        // Assert the dot paths exist and contain the enriched values:
        $this->assertSame(110, $bag['totals']['damage']);
        $this->assertSame(60,  $bag['totals']['healing']);
        $this->assertSame(33,  $bag['totals']['defence']);

        $this->assertSame(0.10, $bag['mods']['base']['damage_mod']);
        $this->assertSame(0.05, $bag['devouring']['light']);
    }

    /**
     * Minimal dot-path setter for this test.
     */
    private function setDot(array &$arr, string $path, mixed $value): void
    {
        $parts = explode('.', $path);
        $ref =& $arr;

        foreach ($parts as $segment) {
            if (!array_key_exists($segment, $ref) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref =& $ref[$segment];
        }

        $ref = $value;
    }

}
