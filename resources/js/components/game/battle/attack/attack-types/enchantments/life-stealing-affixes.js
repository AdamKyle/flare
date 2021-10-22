import Damage from "../../damage";

export default class LifeStealingAffixes {

  constructor(characterCurrentHealth, monsterCurrentHealth) {
    this.battleMessages         = [];
    this.damage                 = new Damage();
    this.characterCurrentHealth = characterCurrentHealth;
    this.monsterCurrentHealth   = monsterCurrentHealth;
  }

  affixesLifeStealing(attacker, defender, stacking, damageDeduction) {
    const details = this.damage.affixLifeSteal(attacker, defender, this.monsterCurrentHealth, this.characterCurrentHealth, stacking, damageDeduction);

    this.monsterCurrentHealth   = details.monsterCurrentHealth;
    this.characterCurrentHealth = details.characterHealth;

    this.battleMessages       = [...this.battleMessages, ...this.damage.getMessages()];
  }

  getMonsterHealth() {
    return this.monsterCurrentHealth;
  }

  getCharacterHealth() {
    return this.characterCurrentHealth;
  }

  getBattleMessages() {
    return this.battleMessages;
  }
}