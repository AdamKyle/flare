<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Values;

use App\Flare\Values\ArmourTypes;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\CharacterInventory\Values\EquippablePositions;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquippablePositionsTest extends TestCase {

    use RefreshDatabase;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testInitializeEquippablePositionValueWithInProperValue() {
        $this->expectException(Exception::class);

        new EquippablePositions(13);
    }

    public function testInitializeEquippablePositionValueWithProperValue() {
        $this->expectNotToPerformAssertions();

        new EquippablePositions(EquippablePositions::ARTIFACT);
    }

    public function testGetEquippablePositions() {
        $positions = [
            EquippablePositions::LEFT_HAND,
            EquippablePositions::RIGHT_HAND,
            EquippablePositions::RING_ONE,
            EquippablePositions::RING_TWO,
            EquippablePositions::SPELL_ONE,
            EquippablePositions::SPELL_TWO,
            EquippablePositions::TRINKET,
            EquippablePositions::ARTIFACT,
            EquippablePositions::SLEEVES,
            EquippablePositions::LEGGINGS,
            EquippablePositions::GLOVES,
            EquippablePositions::SHIELD,
            EquippablePositions::BODY,
            EquippablePositions::FEET,
            EquippablePositions::HELMET,
        ];

        $this->assertEquals($positions, EquippablePositions::equippablePositions());
    }

    public function testGetWeaponsForLeftHand() {
        $this->assertEquals([
            WeaponTypes::WEAPON,
            WeaponTypes::STAVE,
            WeaponTypes::SCRATCH_AWL,
            WeaponTypes::MACE,
            WeaponTypes::GUN,
            WeaponTypes::BOW,
            WeaponTypes::FAN,
            WeaponTypes::HAMMER,
            ArmourTypes::SHIELD,
        ], EquippablePositions::typesForPositions(EquippablePositions::LEFT_HAND));
    }

    public function testGetRingForRingOne() {
        $this->assertEquals([
            WeaponTypes::RING
        ], EquippablePositions::typesForPositions(EquippablePositions::RING_ONE));
    }

    public function testGetArtifactForArtifactPosition() {
        $this->assertEquals([
            EquippablePositions::ARTIFACT
        ], EquippablePositions::typesForPositions(EquippablePositions::ARTIFACT));
    }

    public function testGetTrinketForTrinketPosition() {
        $this->assertEquals([
            EquippablePositions::TRINKET
        ], EquippablePositions::typesForPositions(EquippablePositions::TRINKET));
    }

    public function testGetSleevesForSleevesPosition() {
        $this->assertEquals([
            EquippablePositions::SLEEVES
        ], EquippablePositions::typesForPositions(EquippablePositions::SLEEVES));
    }

    public function testGetHelmetForHelmetPosition() {
        $this->assertEquals([
            EquippablePositions::HELMET
        ], EquippablePositions::typesForPositions(EquippablePositions::HELMET));
    }

    public function testGetGlovesForGlovesPosition() {
        $this->assertEquals([
            EquippablePositions::GLOVES
        ], EquippablePositions::typesForPositions(EquippablePositions::GLOVES));
    }

    public function testGetLeggingsForLeggingsPosition() {
        $this->assertEquals([
            EquippablePositions::LEGGINGS
        ], EquippablePositions::typesForPositions(EquippablePositions::LEGGINGS));
    }

    public function testGetBootsForFeetPosition() {
        $this->assertEquals([
            EquippablePositions::FEET
        ], EquippablePositions::typesForPositions(EquippablePositions::FEET));
    }

    public function testGetBodyForBodyPosition() {
        $this->assertEquals([
            EquippablePositions::BODY
        ], EquippablePositions::typesForPositions(EquippablePositions::BODY));
    }

    public function testGetNothingForInvalidType() {
        $this->assertEquals([], EquippablePositions::typesForPositions('werwerwe'));
    }

    public function testGetSpellsForSpellOne() {
        $this->assertEquals([
            SpellTypes::HEALING,
            SpellTypes::DAMAGE,
        ], EquippablePositions::typesForPositions(EquippablePositions::SPELL_ONE));
    }

    public function testGetOppositeForLeftHand() {
        $this->assertEquals(EquippablePositions::RIGHT_HAND, EquippablePositions::getOppisitePosition(EquippablePositions::LEFT_HAND));
    }

    public function testGetOppositeForRightHand() {
        $this->assertEquals(EquippablePositions::LEFT_HAND, EquippablePositions::getOppisitePosition(EquippablePositions::RIGHT_HAND));
    }

    public function testGetOppositeForRingOne() {
        $this->assertEquals(EquippablePositions::RING_TWO, EquippablePositions::getOppisitePosition(EquippablePositions::RING_ONE));
    }

    public function testGetOppositeForRingTwo() {
        $this->assertEquals(EquippablePositions::RING_ONE, EquippablePositions::getOppisitePosition(EquippablePositions::RING_TWO));
    }

    public function testGetOppositeForSpellOne() {
        $this->assertEquals(EquippablePositions::SPELL_TWO, EquippablePositions::getOppisitePosition(EquippablePositions::SPELL_ONE));
    }

    public function testGetOppositeForSpellTwo() {
        $this->assertEquals(EquippablePositions::SPELL_ONE, EquippablePositions::getOppisitePosition(EquippablePositions::SPELL_TWO));
    }

    public function testGetNullForInvalidOppositeType() {
        $this->assertNull(EquippablePositions::getOppisitePosition('sdfsdf'));
    }
}
