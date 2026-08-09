<?php

namespace Tests\Unit\Game\Character\Values;

use App\Game\Character\Values\CharacterClass;
use Tests\TestCase;

class CharacterClassTest extends TestCase
{
    public function test_get_name_returns_the_enum_value(): void
    {
        $this->assertSame('Fighter', CharacterClass::FIGHTER->getName());
    }

    public function test_get_classes_returns_all_class_values_keyed_by_value(): void
    {
        $classes = CharacterClass::getClasses();

        $this->assertSame('Fighter', $classes['Fighter']);
        $this->assertSame('Beastmaster', $classes['Beastmaster']);
        $this->assertCount(count(CharacterClass::cases()), $classes);
    }

    public function test_is_prisoner_is_true_only_for_prisoner(): void
    {
        $this->assertTrue(CharacterClass::PRISONER->isPrisoner());
        $this->assertFalse(CharacterClass::FIGHTER->isPrisoner());
    }

    public function test_is_gunslinger_is_true_only_for_gunslinger(): void
    {
        $this->assertTrue(CharacterClass::GUNSLINGER->isGunslinger());
        $this->assertFalse(CharacterClass::FIGHTER->isGunslinger());
    }

    public function test_is_dancer_is_true_only_for_dancer(): void
    {
        $this->assertTrue(CharacterClass::DANCER->isDancer());
        $this->assertFalse(CharacterClass::FIGHTER->isDancer());
    }

    public function test_is_book_binder_is_true_only_for_book_binder(): void
    {
        $this->assertTrue(CharacterClass::BOOK_BINDER->isBookBinder());
        $this->assertFalse(CharacterClass::FIGHTER->isBookBinder());
    }

    public function test_is_apothecary_is_true_only_for_apothecary(): void
    {
        $this->assertTrue(CharacterClass::APOTHECARY->isApothecary());
        $this->assertFalse(CharacterClass::FIGHTER->isApothecary());
    }

    public function test_is_buccaneer_is_true_only_for_buccaneer(): void
    {
        $this->assertTrue(CharacterClass::BUCCANEER->isBuccaneer());
        $this->assertFalse(CharacterClass::FIGHTER->isBuccaneer());
    }

    public function test_is_beastmaster_is_true_only_for_beastmaster(): void
    {
        $this->assertTrue(CharacterClass::BEASTMASTER->isBeastmaster());
        $this->assertFalse(CharacterClass::FIGHTER->isBeastmaster());
    }

    public function test_is_fighter_is_true_only_for_fighter(): void
    {
        $this->assertTrue(CharacterClass::FIGHTER->isFighter());
        $this->assertFalse(CharacterClass::HERETIC->isFighter());
    }

    public function test_is_heretic_is_true_only_for_heretic(): void
    {
        $this->assertTrue(CharacterClass::HERETIC->isHeretic());
        $this->assertFalse(CharacterClass::FIGHTER->isHeretic());
    }

    public function test_is_prophet_is_true_only_for_prophet(): void
    {
        $this->assertTrue(CharacterClass::PROPHET->isProphet());
        $this->assertFalse(CharacterClass::FIGHTER->isProphet());
    }

    public function test_is_ranger_is_true_only_for_ranger(): void
    {
        $this->assertTrue(CharacterClass::RANGER->isRanger());
        $this->assertFalse(CharacterClass::FIGHTER->isRanger());
    }

    public function test_is_vampire_is_true_only_for_vampire(): void
    {
        $this->assertTrue(CharacterClass::VAMPIRE->isVampire());
        $this->assertFalse(CharacterClass::FIGHTER->isVampire());
    }

    public function test_is_thief_is_true_only_for_thief(): void
    {
        $this->assertTrue(CharacterClass::THIEF->isThief());
        $this->assertFalse(CharacterClass::FIGHTER->isThief());
    }

    public function test_is_blacksmith_is_true_only_for_blacksmith(): void
    {
        $this->assertTrue(CharacterClass::BLACKSMITH->isBlacksmith());
        $this->assertFalse(CharacterClass::FIGHTER->isBlacksmith());
    }

    public function test_is_arcane_alchemist_is_true_only_for_arcane_alchemist(): void
    {
        $this->assertTrue(CharacterClass::ARCANE_ALCHEMIST->isArcaneAlchemist());
        $this->assertFalse(CharacterClass::FIGHTER->isArcaneAlchemist());
    }

    public function test_is_alcoholic_is_true_only_for_alcoholic(): void
    {
        $this->assertTrue(CharacterClass::ALCOHOLIC->isAlcoholic());
        $this->assertFalse(CharacterClass::FIGHTER->isAlcoholic());
    }

    public function test_is_cleric_is_true_only_for_cleric(): void
    {
        $this->assertTrue(CharacterClass::CLERIC->isCleric());
        $this->assertFalse(CharacterClass::FIGHTER->isCleric());
    }

    public function test_is_merchant_is_true_only_for_merchant(): void
    {
        $this->assertTrue(CharacterClass::MERCHANT->isMerchant());
        $this->assertFalse(CharacterClass::FIGHTER->isMerchant());
    }

    public function test_is_caster_is_true_for_caster_classes_and_false_for_others(): void
    {
        $this->assertTrue(CharacterClass::PROPHET->isCaster());
        $this->assertTrue(CharacterClass::HERETIC->isCaster());
        $this->assertTrue(CharacterClass::ARCANE_ALCHEMIST->isCaster());
        $this->assertTrue(CharacterClass::BOOK_BINDER->isCaster());
        $this->assertTrue(CharacterClass::CLERIC->isCaster());
        $this->assertTrue(CharacterClass::APOTHECARY->isCaster());
        $this->assertFalse(CharacterClass::FIGHTER->isCaster());
    }

    public function test_is_non_caster_is_true_for_non_caster_classes_and_false_for_others(): void
    {
        $this->assertTrue(CharacterClass::FIGHTER->isNonCaster());
        $this->assertTrue(CharacterClass::BLACKSMITH->isNonCaster());
        $this->assertTrue(CharacterClass::RANGER->isNonCaster());
        $this->assertTrue(CharacterClass::THIEF->isNonCaster());
        $this->assertTrue(CharacterClass::VAMPIRE->isNonCaster());
        $this->assertTrue(CharacterClass::PRISONER->isNonCaster());
        $this->assertTrue(CharacterClass::ALCOHOLIC->isNonCaster());
        $this->assertTrue(CharacterClass::MERCHANT->isNonCaster());
        $this->assertTrue(CharacterClass::GUNSLINGER->isNonCaster());
        $this->assertTrue(CharacterClass::BUCCANEER->isNonCaster());
        $this->assertTrue(CharacterClass::BEASTMASTER->isNonCaster());
        $this->assertFalse(CharacterClass::PROPHET->isNonCaster());
    }

    public function test_is_healer_is_true_for_healer_classes_and_false_for_others(): void
    {
        $this->assertTrue(CharacterClass::PROPHET->isHealer());
        $this->assertTrue(CharacterClass::RANGER->isHealer());
        $this->assertTrue(CharacterClass::CLERIC->isHealer());
        $this->assertTrue(CharacterClass::APOTHECARY->isHealer());
        $this->assertFalse(CharacterClass::FIGHTER->isHealer());
    }
}
