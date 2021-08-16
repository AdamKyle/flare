import Monster from '../monster/monster';
import {random} from 'lodash';

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
        this.battleMessages.push({
          message: defender.name + ' blocked the (weapon) attack!'
        });

        this.missed += 1;

        if (attackAgain) {
          return this.attack(defender, attacker, false, 'monster');
        }
      } else {
        this.castSpells(attacker, defender, type);
        this.useArtifacts(attacker, defender, type);
        this.useRings(attacker, defender, type);

        this.doAttack(attacker, type);

        if (type === 'monster') {
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
    let attackerAccuracy = attacker.skills.filter(s => s.name === 'Accuracy')[0].skill_bonus;
    let defenderDodge    = defender.skills.filter(s => s.name === 'Dodge')[0].skill_bonus;
    let toHitBase        = this.toHitCalculation(attacker.to_hit_base, attacker.dex, attackerAccuracy, defenderDodge);

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
    return ((toHit + toHit * accuracy) / 10000) - ((dex / 10000) * dodge);
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
      }
    }
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
      let totalDamage = Math.round(attacker.spell_damage - (attacker.spell_damage * defender.spell_evasion));

      if (totalDamage < 0) {
        this.battleMessages.push({
          message: this.attackerName + '\'s Spells have no effect!'
        });

        return;
      }

      this.monsterCurrentHealth = this.monsterCurrentHealth - totalDamage;

      this.battleMessages.push({
        message: attacker.name + ' spells hit for: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
    }

    if (type === 'monster') {
      const damage   = random(1, attacker.spell_damage);
      let totalDamage = Math.round(damage - (damage * defender.spell_evasion));

      if (totalDamage <= 0) {
        this.battleMessages.push({
          message: attacker.name + '\'s Spells have no effect!'
        });

        return;
      } else if ($totalDamage !== attacker.artifact_damage) {
        this.battleMessages.push({
          message: this.attackerName + '\'s Artifacts have their potency reduced by the enemies artifact annulment'
        });
      }

      this.characterCurrentHealth = this.characterCurrentHealth - totalDamage;

      this.battleMessages.push({
        message: attacker.name + ' spells hit for: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
    }
  }

  artifactDamage(attacker, defender, type) {
    if (type === 'player') {
      let totalDamage = Math.round(attacker.artifact_damage - (attacker.artifact_damage * defender.artifact_annulment));

      if (totalDamage <= 0) {
        this.battleMessages.push({
          message: this.attackerName + '\'s Artifacts are annulled!'
        });

        return;
      } else if (totalDamage !== attacker.artifact_damage) {
        this.battleMessages.push({
          message: this.attackerName + '\'s Artifacts have their potency reduced by the enemies artifact annulment'
        });
      }

      this.monsterCurrentHealth = this.monsterCurrentHealth - totalDamage;

      this.battleMessages.push({
        message: attacker.name + ' artifacts hit for: ' + totalDamage.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
    }

    if (type === 'monster') {
      const damage    = random(1, attacker.artifact_damage);
      let totalDamage = Math.round(damage - (damage * defender.artifact_damage));

      if (totalDamage < 0) {
        this.battleMessages.push({
          message: attacker.name + '\'s Artifacts are annulled!',
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
      this.monsterCurrentHealth = this.monsterCurrentHealth - attacker.attack;

      if (attacker.has_affixes) {
        this.battleMessages.push({
          message: 'The enchantments on your equipment lash out at the enemy!'
        });
      }

      this.battleMessages.push({
        message: attacker.name + ' hit for (weapon) ' + attacker.attack.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
      });
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
