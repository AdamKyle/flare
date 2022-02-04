<?php

namespace Tests\Unit\Game\Core\Values;

use App\Game\Core\Values\FactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactionTypeTest extends TestCase
{
    use RefreshDatabase;

    public function testValueThrowsError() {
        $this->expectException(\Exception::class);

        new FactionType('apples');
    }

    public function testGetMercenaryTitle() {
        $this->assertTrue((new FactionType(FactionType::MERCENARY))->isMercenary());
    }

    public function testGetSoldierTitle() {
        $this->assertTrue((new FactionType(FactionType::SOLDIER))->isSoldier());
    }

    public function testGetSaviourTitle() {
        $this->assertTrue((new FactionType(FactionType::SAVIOUR))->isSaviour());
    }

    public function testGetLegendarySlayerTitle() {
        $this->assertTrue((new FactionType(FactionType::LEGENDARY_SLAYER))->isLegendarySlayer());
    }

    public function testLevelOneIsMercenaryTitle() {
        $this->assertEquals(FactionType::MERCENARY, FactionType::getTitle(1));
    }

    public function testLevelTwoIsSoldierTitle() {
        $this->assertEquals(FactionType::SOLDIER, FactionType::getTitle(2));
    }

    public function testLevelThreeIsSaviourTitle() {
        $this->assertEquals(FactionType::SAVIOUR, FactionType::getTitle(3));
    }

    public function testLevelFourIsLegendaryTitle() {
        $this->assertEquals(FactionType::LEGENDARY_SLAYER, FactionType::getTitle(4));
    }

    public function testLevelFiveIsMythicProtectorTitle() {
        $this->assertEquals(FactionType::MYTHIC_PROTECTOR, FactionType::getTitle(5));
    }

    public function testLevelSixIsNullTitle() {
        $this->assertNull(FactionType::getTitle(6));
    }

}
