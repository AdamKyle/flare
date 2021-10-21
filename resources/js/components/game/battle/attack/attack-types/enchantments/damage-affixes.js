import Damage from "../../damage";

export default class DamageAffixes {

  constructor(characterCurrentHealth, monsterCurrentHealth) {
    this.battleMessages         = [];
    this.damage                 = new Damage();
    this.characterCurrentHealth = characterCurrentHealth;
    this.monsterCurrentHealth   = monsterCurrentHealth;
  }

  fireDamageAffixes(attacker, defender, type) {
    const damage = new Damage();

    this.monsterCurrentHealth = damage.affixDamage(attacker, defender, this.monsterCurrentHealth);
    this.battleMessages       = damage.getMessages();
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