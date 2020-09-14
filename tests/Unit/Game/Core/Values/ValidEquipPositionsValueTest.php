<?php

namespace Tests\Unit\Game\Core\Values;

use App\Game\Core\Values\ValidEquipPositionsValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ValidEquipPositionsValueTest extends TestCase
{
    use RefreshDatabase, CreateItem;


    public function testShieldPositions()
    {
        $item = $this->createItem([
            'name' => 'sample',
            'type' => 'shield'
        ]);

        $positions = (new ValidEquipPositionsValue())->getPositions($item);

        $this->assertEquals($positions, ['left-hand', 'right-hand']);
    }

    public function testSpellHealingPositions()
    {
        $item = $this->createItem([
            'name' => 'sample',
            'type' => 'spell-healing'
        ]);

        $positions = (new ValidEquipPositionsValue())->getPositions($item);

        $this->assertEquals($positions, ['spell-one', 'spell-two']);
    }

    public function testRingPositions()
    {
        $item = $this->createItem([
            'name' => 'sample',
            'type' => 'ring'
        ]);

        $positions = (new ValidEquipPositionsValue())->getPositions($item);

        $this->assertEquals($positions, ['ring-one', 'ring-two']);
    }

    public function testArtifactPositions()
    {
        $item = $this->createItem([
            'name' => 'sample',
            'type' => 'artifact'
        ]);

        $positions = (new ValidEquipPositionsValue())->getPositions($item);

        $this->assertEquals($positions, ['artifact-one', 'artifact-two']);
    }

    public function testUnknownPosition()
    {
        $item = $this->createItem([
            'name' => 'sample',
            'type' => '???'
        ]);

        $positions = (new ValidEquipPositionsValue())->getPositions($item);

        $this->assertTrue(empty($positions));
    }
}
