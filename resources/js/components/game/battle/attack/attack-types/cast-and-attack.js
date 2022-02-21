import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import WeaponAttack from "./weapon-attack";
import CastAttack from "./cast-attack";
import MagicAndWeaponAttackBase from "./magic-and-weapon-attack-base";

export default class CastAndAttack extends MagicAndWeaponAttackBase {

  constructor(attacker, defender, characterCurrentHealth, monsterHealth, voided) {
    super(attacker, defender, characterCurrentHealth, monsterHealth, voided);
  }

  doAttack() {
    const attackData       = this.attacker.attack_types[this.voided ? AttackType.VOIDED_CAST_AND_ATTACK : AttackType.CAST_AND_ATTACK];

    const canEntranceEnemy = new CanEntranceEnemy();

    const canEntrance      = canEntranceEnemy.canEntranceEnemy(attackData, this.defender, 'player')

    const castAttack       = new CastAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    const canHitCheck      = new CanHitCheck();

    const canCast           = canHitCheck.canCast(this.attacker, this.defender, this.battleMessages);
    const canHit            = canHitCheck.canHit(this.attacker, this.defender, this.battleMessages);

    if (canHitCheck.getCanAutoHit()) {
      return this.autoCastAndAttack(attackData, castAttack, canHitCheck, canEntrance)
    }

    if (canEntrance) {
      return this.entrancedCastThenAttack(attackData, castAttack, canEntranceEnemy, canEntrance)
    }

    this.castAttack(attackData, castAttack, canCast);

    this.setStateInfo(castAttack);

    const weaponAttack     = new WeaponAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    this.weaponAttack(attackData, weaponAttack, canHit);

    this.setStateInfo(weaponAttack);

    this.useItems(attackData, this.attacker.class);

    return this.setState();
  }
}
