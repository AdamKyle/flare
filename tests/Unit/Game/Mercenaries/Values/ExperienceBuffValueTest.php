<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Mercenaries\Values\ExperienceBuffValue;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ExperienceBuffValueTest extends TestCase {

    use RefreshDatabase, CreateItem;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testInvalidTypeError() {
        $this->expectException(\Exception::class);

        new ExperienceBuffValue('40');
    }

    public function testExperienceBuffSelectionIsNotEmpty() {
        $this->assertNotEmpty(ExperienceBuffValue::buffSelection());
    }

    public function testGetCostForRankOne() {
        $this->assertEquals(ExperienceBuffValue::RANK_ONE_COST, (new ExperienceBuffValue(ExperienceBuffValue::RANK_ONE))->getCost());
    }

    public function testGetCostForRankTwo() {
        $this->assertEquals(ExperienceBuffValue::RANK_TWO_COST, (new ExperienceBuffValue(ExperienceBuffValue::RANK_TWO))->getCost());
    }

    public function testGetCostForRankThree() {
        $this->assertEquals(ExperienceBuffValue::RANK_THREE_COST, (new ExperienceBuffValue(ExperienceBuffValue::RANK_THREE))->getCost());
    }

    public function testGetCostForRankFour() {
        $this->assertEquals(ExperienceBuffValue::RANK_FOUR_COST, (new ExperienceBuffValue(ExperienceBuffValue::RANK_FOUR))->getCost());
    }

    public function testGetCostForRankFive() {
        $this->assertEquals(ExperienceBuffValue::RANK_FIVE_COST, (new ExperienceBuffValue(ExperienceBuffValue::RANK_FIVE))->getCost());
    }

    public function testGetCostForRankSix() {
        $this->assertEquals(ExperienceBuffValue::RANK_SIX_COST, (new ExperienceBuffValue(ExperienceBuffValue::RANK_SIX))->getCost());
    }

    public function testGetCostForRankSeven() {
        $this->assertEquals(ExperienceBuffValue::RANK_SEVEN_COST, (new ExperienceBuffValue(ExperienceBuffValue::RANK_SEVEN))->getCost());
    }

    public function testGetXPBuffForRankOne() {
        $this->assertEquals(ExperienceBuffValue::RANK_ONE_AMOUNT, (new ExperienceBuffValue(ExperienceBuffValue::RANK_ONE))->getXPBuff());
    }

    public function testGetXPBuffForRankTwo() {
        $this->assertEquals(ExperienceBuffValue::RANK_TWO_AMOUNT, (new ExperienceBuffValue(ExperienceBuffValue::RANK_TWO))->getXPBuff());
    }

    public function testGetXPBuffForRankThree() {
        $this->assertEquals(ExperienceBuffValue::RANK_THREE_AMOUNT, (new ExperienceBuffValue(ExperienceBuffValue::RANK_THREE))->getXPBuff());
    }

    public function testGetXPBuffForRankFour() {
        $this->assertEquals(ExperienceBuffValue::RANK_FOUR_AMOUNT, (new ExperienceBuffValue(ExperienceBuffValue::RANK_FOUR))->getXPBuff());
    }

    public function testGetXPBuffForRankFive() {
        $this->assertEquals(ExperienceBuffValue::RANK_FIVE_AMOUNT, (new ExperienceBuffValue(ExperienceBuffValue::RANK_FIVE))->getXPBuff());
    }

    public function testGetXPBuffForRankSix() {
        $this->assertEquals(ExperienceBuffValue::RANK_SIX_AMOUNT, (new ExperienceBuffValue(ExperienceBuffValue::RANK_SIX))->getXPBuff());
    }

    public function testGetXPBuffForRankSeven() {
        $this->assertEquals(ExperienceBuffValue::RANK_SEVEN_AMOUNT, (new ExperienceBuffValue(ExperienceBuffValue::RANK_SEVEN))->getXPBuff());
    }
}
