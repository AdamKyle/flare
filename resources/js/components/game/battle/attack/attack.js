import Monster from '../monster/monster';
import {random} from 'lodash';
import Damage from "./damage";
import Healing from "./healing";

export default class Attack {

  constructor(attacker, defender, characterCurrentHealth, monsterCurrentHealth) {
    this.attacker = attacker;
    this.defender = defender;
    this.characterCurrentHealth = characterCurrentHealth;
    this.characterMaxHealth = characterCurrentHealth;
    this.monsterCurrentHealth = monsterCurrentHealth;
    this.battleMessages = [];
    this.attackerName = '';
    this.missed       = 0;
  }

  attack(attacker, defender, attackAgain, type) {
    this.attackerName = attacker.name;

    if (this.isMonsterDead()) {
      this.battleMessages.push({
        message: this.defender.name + ' has been defeated!'
      });

      this.monsterCurrentHealth = 0;

      return this;
    }

    if (this.isCharacterDead()) {
      this.battleMessages.push({
        message: 'You must resurrect first!'
      });

      this.characterCurrentHealth = 0;

      return this;
    }

    if (!this.canHit(attacker, defender, type)) {
      this.affixesFire(attacker, defender, type);
      this.castSpells(attacker, defender, type);
      this.useArtifacts(attacker, defender, type);
      this.useRings(attacker, defender, type);

      this.battleMessages.push({
        message: this.attackerName + ' (weapon) missed!'
      });

      this.missed += 1;

      if (attackAgain) {
        return this.attack(defender, attacker, false, 'monster');
      }
    } else {
      if (this.blockedAttack(defender, attacker, type)) {
        this.affixesFire(attacker, defender, type);
        this.useArtifacts(attacker, defender, type);
        this.useRings(attacker, defender, type);

        this.battleMessages.push({
          message: defender.name + ' blocked the (weapon) attack!'
        });

        this.missed += 1;

        if (attackAgain) {
          return this.attack(defender, attacker, false, 'monster');
        }
      } else {
        this.affixesFire(attacker, defender, type);
        this.castSpells(attacker, defender, type);
        this.useArtifacts(attacker, defender, type);
        this.useRings(attacker, defender, type);

        this.doAttack(attacker, type);

        if (type === 'monster') {
          this.affixesLifeStealing(defender, attacker, new Damage());
          this.healSelf(defender);
        }

        if (attackAgain) {
          return this.attack(defender, attacker, false, 'monster');
        }
      }
    }

    return this;
  }

  getState() {
    return {
      characterCurrentHealth: this.characterCurrentHealth,
      monsterCurrentHealth: this.monsterCurrentHealth,
      battleMessages: this.battleMessages,
      missCounter: this.missed
    }
  }

  canHit(attacker, defender) {
    const damage         = new Damage();

    if (attacker.hasOwnProperty('class')) {
      if (damage.canAutoHit(attacker)) {
        this.battleMessages = [...this.battleMessages, ...damage.getMessages()];

        return true;
      }
    }

    let attackerAccuracy = attacker.skills.filter(s => s.name === 'Accuracy')[0].skill_bonus;
    let defenderDodge    = defender.skills.filter(s => s.name === 'Dodge')[0].skill_bonus;
    let toHitBase        = this.toHitCalculation(attacker.to_hit_base, attacker.dex, attackerAccuracy, defenderDodge);

    if (attackerAccuracy > 1.0) {
      return true;
    }

    if (defenderDodge > 1.0) {
       return false;
    }

    if (Math.sign(toHitBase) === - 1) {
      toHitBase = Math.abs(toHitBase);
    }

    if (toHitBase > 1.0) {
      return true;
    }
    const percentage = Math.floor((100 - toHitBase));

    const needToHit = 100 - percentage;

    return (Math.random() * (100 - 1) + 1) > needToHit;
  }

  toHitCalculation(toHit, dex, accuracy, dodge) {
    const enemyDex = (dex / 10000);
    const hitChance = ((toHit + toHit * accuracy) / 100);

    return (enemyDex + enemyDex * dodge) - hitChance;
  }

  affixesFire(attacker, defender, type) {
    if (type === 'player') {
      const damage = new Damage();

      if (attacker.class === 'Vampire') {
        this.affixesLifeStealing(attacker, defender, damage);
      } else {
        this.monsterCurrentHealth = damage.affixDamage(attacker, defender, this.monsterCurrentHealth);
      }

      this.battleMessages       = [...this.battleMessages, ...damage.getMessages()];
    }
  }

  affixesLifeStealing(attacker, defender, damage) {
    const details = damage.affixLifeSteal(attacker, defender, this.monsterCurrentHealth, this.characterCurrentHealth);

    this.monsterCurrentHealth = details.monsterCurrentHealth;
    this.characterCurrentHealth = details.characterHealth;

    this.battleMessages       = [...this.battleMessages, ...damage.getMessages()];
  }

  castSpells(attacker, defender, type) {
    if (type == 'player') {
      if (attacker.has_damage_spells && attacker.spell_damage !== 0) {
        this.battleMessages.push({
          message: 'Your spells burst forward towards the enemy!'
        });

        this.spellDamage(attacker, defender, type);
      }
    } else {
      if (attacker.has_damage_spells && attacker.spell_damage !== 0) {
        this.battleMessages.push({
          message: 'The enemy begins to cast their spells!'
        });

        this.spellDamage(attacker, defender, type);
      }
    }
  }

  healSelf(defender) {
    if (defender.heal_for > 0 && (this.characterCurrentHealth > 0 && this.characterCurrentHealth !== this.characterMaxHealth)) {
      this.characterCurrentHealth += defender.heal_for;

      this.battleMessages.push({
        message: 'Light floods your eyes as your wounds heal over for: ' + defender.heal_for.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
      });

      this.extraHealing(defender);
    }

    if (this.characterCurrentHealth <= 0 && defender.resurrection_chance !== 0.0) {
      let dc = 100 - (100 * defender.resurrection_chance);

      const characterRoll = (Math.random() * (100 - 1) + 1) > dc;

      if (characterRoll) {
        this.characterCurrentHealth = 0;
        this.characterCurrentHealth += defender.heal_for;

        this.battleMessages.push({
          message: 'Breath enters your lungs and you come back to life, healing for: ' + defender.heal_for.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
        });

        this.extraHealing(defender);
      }
    }
  }

  extraHealing(defender) {
    const healing = new Healing();

    this.characterCurrentHealth = healing.extraHealing(defender, this.characterCurrentHealth);

    this.battleMessages = [...this.battleMessages, ...healing.getMessages()];
  }

  useArtifacts(attacker, defender, type) {
    if (type == 'player') {
      if (attacker.has_artifacts && attacker.artifact_damage !== 0) {
        this.battleMessages.push({
          message: 'Your artifacts glow before the enemy!'
        });

        this.artifactDamage(attacker, defender, type);
      }
    } else {
      if (attacker.has_artifacts && attacker.artifact_damage !== 0) {
        this.battleMessages.push({
          message: 'The enemies artifacts glow brightly!'
        });

        this.artifactDamage(attacker, defender, type);
      }
    }
  }

  useRings(attacker, defender, type) {
    if (type === 'player') {
      if (attacker.ring_damage !== 0) {
        this.battleMessages.push({
          message: 'Your rings shimmer in the presence of this foe ...'
        });

        this.ringDamage(attacker, defender, type);
      }
    }
  }

  spellDamage(attacker, defender, type) {
    if (type === 'player') {
      const damage = new Damage();

      this.monsterCurrentHealth = damage.spellDamage(attacker, defender, this.monsterCurrentHealth);
      this.battleMessages       = [...this.battleMessages, ...damage.getMessages()];
    }

    if (type === 'monster') {
      const dc        = 100 - (100 * defender.spell_evasion);
      let totalDamage = attacker.spell_damage;

      if (dc <= 0 || random(1, 100) > dc) {
        this.battleMessages.push({
          message: this.attackerName + '\'s Spells failed to do anything.'
        });

        return;
      }

      this.characterCurrentHealth = this.characterCurrentHealth - totalDamage;

      this.battleMessages.push({
        message: attacker.name + ' spells hit for: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
    }
  }

  artifactDamage(attacker, defender, type) {
    if (type === 'player') {
      const dc        = 100 - (100 * defender.artifact_annulment);
      let totalDamage = attacker.artifact_damage;

      if (dc <= 0 || random(1, 100) > dc) {
        this.battleMessages.push({
          message: this.attackerName + '\'s Artifacts are annulled!'
        });

        return;
      }

      this.monsterCurrentHealth = this.monsterCurrentHealth - totalDamage;

      this.battleMessages.push({
        message: attacker.name + ' artifacts hit for: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
    }

    if (type === 'monster') {
      const dc        = 100 - (100 * defender.artifact_annulment);
      let totalDamage = attacker.artifact_damage;

      if (dc <= 0 || random(1, 100) > dc) {
        this.battleMessages.push({
          message: this.attackerName + '\'s Artifacts are annulled!'
        });

        return;
      }

      this.characterCurrentHealth = this.characterCurrentHealth - totalDamage;

      this.battleMessages.push({
        message: attacker.name + ' artifacts hit for: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
    }
  }

  ringDamage(attacker, defender, type) {
    if (type === 'player') {
      this.monsterCurrentHealth = this.monsterCurrentHealth - attacker.ring_damage;

      this.battleMessages.push({
        message: attacker.name + ' rings hit for: ' + attacker.ring_damage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
    }
  }

  blockedAttack(defender, attacker) {
    return defender.ac > attacker.base_stat;
  }

  isMonsterDead() {
    return this.monsterCurrentHealth <= 0;
  }

  isCharacterDead() {
    return this.characterCurrentHealth <= 0;
  }

  doAttack(attacker, type) {

    if (type === 'player') {
      const damage = new Damage();

      this.monsterCurrentHealth = damage.doAttack(attacker, this.monsterCurrentHealth);
      this.battleMessages = [...this.battleMessages, ...damage.getMessages()];
    }

    if (type === 'monster') {
      const monster = new Monster(attacker);
      const attack = monster.attack();

      this.characterCurrentHealth = this.characterCurrentHealth - attack;

      this.battleMessages.push({
        message: attacker.name + ' hit for (weapon) ' + attack.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
    }
  }
}
