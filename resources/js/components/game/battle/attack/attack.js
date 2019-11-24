export default class Attack {

  construct(attacker, defender, characterCurrenthealth, monsterCurrenthealth) {
    this.attacker               = attacker;
    this.defender               = defender;
    this.characterCurrentHealth = characterCurrenthealth;
    this.monsterCurrentHealth   = monsterCurrenthealth;
    this.battleMessages         = [];
  }

  attack() {

  }

  canHit() {

  }

  dodgedAttack() {

  }

  blockedAttack() {

  }

  isMonsterDead() {
    return this.monsterCurrentHealth > 0;
  }

  isCharacterDead() {
    return this.characterCurrentHealth > 0;
  }

  getState() {
    return {
      characterCurrentHealth: this.characterCurrentHealth,
      monsterCurrentHealth:   this.monsterCurrentHealth,
      battleMessages:         this.battleMessages,
    }
  }
}
