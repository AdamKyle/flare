<?php

namespace Tests\Unit\Flare\Values;

use Tests\TestCase;
use App\Flare\Values\AttackTypeValue;


class AttackTypeTest extends TestCase {

    public function testThrowError() {

        $this->expectException(\Exception::class);

        new AttackTypeValue('67');
    }

    public function testIsAttack() {
        $this->assertTrue((new AttackTypeValue(AttackTypeValue::ATTACK))->isAttack());
    }

    public function testIsVoidedAttack() {
        $this->assertTrue((new AttackTypeValue(AttackTypeValue::VOIDED_ATTACK))->isVoidedAttack());
    }

    public function testIsCast() {
        $this->assertTrue((new AttackTypeValue(AttackTypeValue::CAST))->isCast());
    }

    public function testIsVoidedCast() {
        $this->assertTrue((new AttackTypeValue(AttackTypeValue::VOIDED_CAST))->isVoidedCast());
    }

    public function testIsCastAndAttack() {
        $this->assertTrue((new AttackTypeValue(AttackTypeValue::CAST_AND_ATTACK))->isCastAndAttack());
    }

    public function testIsVoidedCastAndAttack() {
        $this->assertTrue((new AttackTypeValue(AttackTypeValue::VOIDED_CAST_AND_ATTACK))->isVoidedCastAndAttack());
    }

    public function testIsAttackAndCast() {
        $this->assertTrue((new AttackTypeValue(AttackTypeValue::ATTACK_AND_CAST))->isAttackAndCast());
    }

    public function testIsVoidedAttackAndCast() {
        $this->assertTrue((new AttackTypeValue(AttackTypeValue::VOIDED_ATTACK_AND_CAST))->isVoidedAttackAndCast());
    }

    public function testIsDefend() {
        $this->assertTrue((new AttackTypeValue(AttackTypeValue::DEFEND))->isDefend());
    }

    public function testIsVoidedDefend() {
        $this->assertTrue((new AttackTypeValue(AttackTypeValue::VOIDED_DEFEND))->isVoidedDefend());
    }
}
