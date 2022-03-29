import {canCounter, canCounterAgain, canEnemyCounter} from "./can-counter";
import {randomNumber} from "../../../../helpers/random_number";

export default class CounterHandler {

  constructor() {
    this.battleMessages = [];
  }

  playerCounter(attacker, defender, isAttackerVoided, monsterHealth, characterHealth) {
    if (canCounter(attacker, defender.getMonster())) {
      monsterHealth = this.playerCounterAttack(attacker, monsterHealth, isAttackerVoided);

      if (monsterHealth > 0) {
        if (canCounterAgain()) {
          characterHealth = this.monsterCounterPlayerCounterAttack(defender.getMonster(), characterHealth);
        }
      }
    }

    return {
      monster_health: monsterHealth,
      character_health: characterHealth,
    }
  }

  enemyCounter(attacker, defender, isDefenderVoided, monsterHealth, characterHealth) {
    if (canEnemyCounter(attacker, defender)) {
      characterHealth = this.monsterCounterAttack(attacker, characterHealth);

      if (characterHealth > 0 && canCounterAgain()) {
        monsterHealth = this.playerCounterMonsterCounterAttack(defender, monsterHealth, isDefenderVoided);
      }
    }

    return {
      monster_health: monsterHealth,
      character_health: characterHealth,
    }
  }

  playerCounterAttack(character, monsterHealth, isAttackVoided) {
    this.addMessage('You manage to lash out the enemy in a counter move with your weapon!');

    let damage = (character.weapon_attack * 0.05).toFixed(0);

    if (isAttackVoided) {
      damage = (character.voided_weapon_attack * 0.05).toFixed(0);
    }

    this.addMessage('Countering the enemy you manage to do: ' + this.formatNumber(damage));

    return monsterHealth - parseInt(damage);
  }

  playerCounterMonsterCounterAttack(character, monsterHealth, isAttackVoided) {
    this.addMessage('You manage to counter the enemies counter!');

    let damage = (character.weapon_attack * 0.025).toFixed(0);

    if (isAttackVoided) {
      damage = (character.voided_weapon_attack * 0.025).toFixed(0);
    }

    this.addMessage('Countering the enemies counter, you manage to do: ' + this.formatNumber(damage));

    return monsterHealth - parseInt(damage);
  }

  monsterCounterAttack(monster, monsterHealth) {
    this.addEnemyActionMessage('The enemy manages to counter your attack!');

    let damage = (this.monsterAttack(monster) * 0.05).toFixed(0);

    this.addEnemyActionMessage('Lashing out the enemy does ' + this.formatNumber(damage));

    return monsterHealth - parseInt(damage);
  }

  monsterCounterPlayerCounterAttack(monster, characterHealth) {
    this.addMessage('The enemy manages to counter your counter!');

    let damage = (monster.attack() * 0.025).toFixed(0);

    this.addMessage('lashing out the enemy strikes you for: ' + this.formatNumber(damage));

    return characterHealth - parseInt(damage);
  }

  getMessages() {
    return this.battleMessages;
  }

  resetMessages() {
    this.battleMessages = [];
  }

  formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  addMessage(message) {
    this.battleMessages.push({message: message, class: 'action-fired'});
  }

  addEnemyActionMessage(message) {
    this.battleMessages.push({message: message, class: 'enemy-action-fired'});
  }

  monsterAttack(monster) {
    const attackRange = monster.attack_range.split('-');

    let attack = randomNumber(attackRange[0], attackRange[1]) + (monster[monster.damage_stat] / 2);

    attack = attack + attack * monster.increases_damage_by;

    return parseInt(attack);
  }

}
