<?php

namespace Tests\Unit\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterInventoryCountTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\CharacterTopBarTransformer;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterTopBarTransformerTest extends TestCase
{
    use RefreshDatabase;

    private Character $character;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testTopBarDirectTransformRetainsCompleteExistingFieldContract(): void
    {
        $data = resolve(CharacterTopBarTransformer::class)->transform($this->character);

        $this->assertSame([
            'attack',
            'health',
            'ac',
            'level',
            'max_level',
            'xp',
            'xp_next',
            'str_modded',
            'dur_modded',
            'dex_modded',
            'chr_modded',
            'int_modded',
            'agi_modded',
            'focus_modded',
            'gold',
            'gold_dust',
            'shards',
            'copper_coins',
            'is_silenced',
            'can_talk_again_at',
            'force_name_change',
            'is_banned',
        ], array_keys($data));
        $this->assertIsString($data['level']);
        $this->assertIsString($data['max_level']);
        $this->assertIsString($data['gold']);
        $this->assertIsString($data['gold_dust']);
        $this->assertIsString($data['shards']);
        $this->assertIsString($data['copper_coins']);
        $this->assertIsInt($data['xp']);
        $this->assertIsInt($data['xp_next']);
        $this->assertIsInt($data['attack']);
        $this->assertIsNumeric($data['ac']);
        $this->assertIsNumeric($data['health']);
        $this->assertSame($this->character->user->is_silenced, $data['is_silenced']);
        $this->assertSame($this->character->force_name_change, $data['force_name_change']);
        $this->assertSame($this->character->user->is_banned, $data['is_banned']);
    }

    public function testTopBarFractalOutputIncludesInventoryCount(): void
    {
        $manager = resolve(Manager::class);
        $data = $manager->createData(
            new Item($this->character, resolve(CharacterTopBarTransformer::class))
        )->toArray();
        $inventoryCount = $manager->createData(
            new Item($this->character, resolve(CharacterInventoryCountTransformer::class))
        )->toArray();
        $direct = resolve(CharacterTopBarTransformer::class)->transform($this->character);

        $this->assertArrayHasKey('inventory_count', $data);
        $this->assertSame($inventoryCount, $data['inventory_count']);
        $this->assertSame($direct, array_intersect_key($data, $direct));
    }

    public function testTopBarSharesCharacterStatisticsWithBaseCharacterTransformer(): void
    {
        $keys = [
            'attack',
            'health',
            'ac',
            'level',
            'max_level',
            'xp',
            'xp_next',
            'str_modded',
            'dur_modded',
            'dex_modded',
            'chr_modded',
            'int_modded',
            'agi_modded',
            'focus_modded',
            'gold',
            'gold_dust',
            'shards',
            'copper_coins',
        ];
        $topBar = resolve(CharacterTopBarTransformer::class)->transform($this->character->refresh());
        $baseCharacter = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character->refresh());

        $this->assertSame(
            array_replace(array_flip($keys), array_intersect_key($topBar, array_flip($keys))),
            array_replace(array_flip($keys), array_intersect_key($baseCharacter, array_flip($keys)))
        );
    }

    public function testTopBarWithStartingEquipmentPreservesCalculatedFields(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->equipStartingEquipment()
            ->getCharacter();
        $data = resolve(CharacterTopBarTransformer::class)->transform($character);
        $builder = resolve(CharacterStatBuilder::class)->setCharacter($character);

        $this->assertSame($builder->statMod('str'), $data['str_modded']);
        $this->assertSame($builder->statMod('dur'), $data['dur_modded']);
        $this->assertSame($builder->statMod('dex'), $data['dex_modded']);
        $this->assertSame($builder->statMod('chr'), $data['chr_modded']);
        $this->assertSame($builder->statMod('int'), $data['int_modded']);
        $this->assertSame($builder->statMod('agi'), $data['agi_modded']);
        $this->assertSame($builder->statMod('focus'), $data['focus_modded']);
        $this->assertSame($builder->buildTotalAttack(), $data['attack']);
        $this->assertSame($builder->buildHealth(), $data['health']);
        $this->assertSame($builder->buildDefence(), $data['ac']);
    }
}
