import CanHitCheck from "./can-hit-check";
import AttackType from "../attack-type";
import CanEntranceEnemy from "./enchantments/can-entrance-enemy";
import WeaponAttack from "./weapon-attack";
import CastAttack from "./cast-attack";
import MagicAndWeaponAttackBase from "./magic-and-weapon-attack-base";

export default class AttackAndCast extends MagicAndWeaponAttackBase {

  constructor(attacker, defender, characterCurrentHealth, monsterHealth, voided) {
    super(attacker, defender, characterCurrentHealth, monsterHealth, voided);
  }

  doAttack() {
    const attackData       = this.attacker.attack_types[this.voided ? AttackType.VOIDED_ATTACK_AND_CAST : AttackType.ATTACK_AND_CAST];

    const canEntranceEnemy = new CanEntranceEnemy();

    const canEntrance      = canEntranceEnemy.canEntranceEnemy(attackData, this.defender, 'player')

    const canHitCheck       = new CanHitCheck();

    const canCast           = canHitCheck.canCast(this.attacker, this.defender, this.battleMessages);
    const canHit            = canHitCheck.canHit(this.attacker, this.defender, this.battleMessages);

    if (canHitCheck.getCanAutoHit()) {
      return this.autoAttackAndCast(attackData, canHitCheck, canEntrance)
    }

    if (canEntrance) {
      return this.entrancedWeaponThenCastAttack(attackData, canEntranceEnemy, canEntrance);
    }

    const weaponAttack     = new WeaponAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    this.weaponAttack(attackData, weaponAttack, canHitCheck, canHit);

    const weaponState = this.setStateInfo(weaponAttack);

    if (weaponState.characterCurrentHealth <= 0 || weaponState.monsterCurrentHealth <= 0) {
      return this.setState();
    }

    const castAttack       = new CastAttack(this.attacker, this.defender, this.characterCurrentHealth, this.monsterHealth, this.voided);

    this.castAttack(attackData, castAttack, canHitCheck, canCast);

    const castState = this.setStateInfo(castAttack);

    if (castState.characterCurrentHealth <= 0 || castState.monsterCurrentHealth <= 0) {
      return this.setState();
    }

    this.useItems(attackData, this.attacker.class);

    return this.setState();
  }


}
