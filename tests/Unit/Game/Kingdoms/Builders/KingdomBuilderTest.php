<?php

namespace Tests\Unit\Game\Kingdoms\Builders;

use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomBuilderTest extends TestCase
{
    use RefreshDatabase;

    private ?CharacterFactory $character;

    private ?KingdomBuilder $kingdomBuilder;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation()
            ->createPassiveForCharacter(PassiveSkillTypeValue::RESOURCE_INCREASE, [
                'current_level' => 10,
                'max_level' => 5,
                'resource_bonus_per_level' => 10,
            ]);

        $this->kingdomBuilder = resolve(KingdomBuilder::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->kingdomBuilder = null;
    }

    public function testCreateKingdomUsesClampedPassiveResourceIncreaseAmount()
    {
        $character = $this->character->getCharacter();

        $kingdom = $this->kingdomBuilder->createKingdom($character->refresh(), 'Sample Kingdom', '#ffffff');

        $this->assertEquals(2050, $kingdom->max_stone);
        $this->assertEquals(2050, $kingdom->max_wood);
        $this->assertEquals(2050, $kingdom->max_clay);
        $this->assertEquals(2050, $kingdom->max_iron);
        $this->assertEquals(150, $kingdom->max_population);
    }
}