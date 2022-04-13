import {random} from 'lodash';
import DamageAffixes from "./enchantments/damage-affixes";
import LifeStealingAffixes from "./enchantments/life-stealing-affixes";
import BattleBase from "../../../battle-base";
import {formatNumber} from "../../../../../format-number";

export default class UseItems extends BattleBase {

  constructor(defender, monsterCurrentHealth, characterCurrentHealth) {
    super();

    this.monsterCurrentHealth   = monsterCurrentHealth;
    this.characterCurrentHealth = characterCurrentHealth;
    this.defender               = defender;
    this.battleMessages         = [];
  }

  useItems(attackData, attackerClass, isVoided) {

    if (!isVoided) {
      if (attackerClass === 'Vampire') {
        this.lifeStealingAffixes(attackData, true, isVoided);
      }

      const damageAffixes = new DamageAffixes(this.characterCurrentHealth, this.monsterCurrentHealth);

      damageAffixes.fireDamageAffixes(attackData.affixes, this.defender, attackData.damage_deduction);

      this.characterCurrentHealth = damageAffixes.getCharacterHealth();
      this.monsterCurrentHealth = damageAffixes.getMonsterHealth();

      this.mergeMessages(damageAffixes.getBattleMessages());
    }

    this.useArtifacts(attackData, this.defender, 'player');
    this.ringDamage(attackData, this.defender, 'player');
  }

  getBattleMessage() {
    return this.getMessages();
  }

  getCharacterCurrentHealth() {
    return this.characterCurrentHealth;
  }

  getMonsterCurrentHealth() {
    return this.monsterCurrentHealth;
  }

  lifeStealingAffixes(attackData, stacking) {
    const lifeStealingAffixes = new LifeStealingAffixes(this.characterCurrentHealth, this.monsterCurrentHealth)

    lifeStealingAffixes.affixesLifeStealing(attackData.affixes, this.defender, stacking, attackData.damage_deduction);

    this.characterCurrentHealth = lifeStealingAffixes.getCharacterHealth();
    this.monsterCurrentHealth   = lifeStealingAffixes.getMonsterHealth();

    this.mergeMessages(lifeStealingAffixes.getBattleMessages());
  }

  useArtifacts(attacker, defender, type) {
    if (type == 'player') {
      if (attacker.artifact_damage !== 0) {
        this.addMessage('Your artifacts glow before the enemy!', 'regular');

        this.artifactDamage(attacker, defender, type);

      }
    } else {
      if (attacker.artifact_damage !== 0) {

        this.addMessage('The enemy\'s artifacts glow brightly!');

        this.artifactDamage(attacker, defender, type);
      }
    }
  }

  artifactDamage(attacker, defender, type) {

    if (type === 'player') {

      const dc        = 100 - (100 * defender.artifact_annulment);
      let totalDamage = attacker.artifact_damage - attacker.artifact_damage * attacker.damage_deduction;

      if (dc <= 0 || random(1, 100) > dc) {
        this.addMessage(attacker.name + '\'s artifacts are annulled!', 'regular');

        return;
      }

      this.monsterCurrentHealth = this.monsterCurrentHealth - totalDamage;

      this.addMessage(attacker.name + '\'s artifacts hit for: ' + formatNumber(Math.ceil(totalDamage)), 'player-action');
    }

    if (type === 'monster') {
      const dc        = 100 - (100 * defender.artifact_annulment);
      let totalDamage = attacker.artifact_damage;

      if (dc <= 0 || random(1, 100) > dc) {
        this.addMessage(attacker.name + '\'s artifacts are annulled!', 'regular');

        return;
      }

      this.characterCurrentHealth = this.characterCurrentHealth - totalDamage;

      this.addEnemyActionMessage(attacker.name + '\'s artifacts hit for: ' + formatNumber(totalDamage), 'enemy-action');
    }
  }

  ringDamage(attacker, defender, type) {
    if (type === 'player' && attacker.ring_damage > 0) {
      const totalDamage = attacker.ring_damage - attacker.ring_damage * attacker.damage_deduction;

      this.monsterCurrentHealth = this.monsterCurrentHealth - totalDamage;

      this.addMessage('Your rings hit for: ' + formatNumber(Math.ceil(totalDamage)), 'player-action');
    }
  }
}
