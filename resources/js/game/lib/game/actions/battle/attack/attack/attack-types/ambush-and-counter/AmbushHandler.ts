import Monster from "../../../../monster/monster";
import {random} from "lodash";
import BattleBase from "../../../../battle-base";
import {formatNumber} from "../../../../../../format-number";


export default class AmbushHandler extends BattleBase {

    handleAmbush(character: any, monster: Monster, characterHealth: number, monsterHealth: number, isCharacterVoided: boolean): {monster_health: number, character_health: number} {

        let healthObject: {monster_health: number, character_health: number}  = {monster_health: monsterHealth, character_health: monsterHealth};

        if (monster.plane() === 'Purgatory') {
            healthObject = this.enemyAmbushesPlayer(character, monster, characterHealth, monsterHealth, isCharacterVoided);
        } else {
            healthObject = this.playerAmbushesPlayer(character, monster, characterHealth, monsterHealth, isCharacterVoided);
        }


        if (healthObject.character_health <= 0) {
            this.addMessage('The enemies ambush has slaughtered you!', 'enemy-action');
        }

        if (healthObject.monster_health <= 0) {
            this.addMessage('Your ambush has slaughtered the enemy!', 'enemy-action');
        }

        return healthObject;
    }

    enemyAmbushesPlayer(character: any, monster: Monster, characterHealth: number, monsterHealth: number, isCharacterVoided: boolean): {monster_health: number, character_health: number} {

        if (this.canEnemyAmbush(monster, character)) {
            this.addMessage('The enemies plotting and scheming comes to fruition!', 'enemy-action');

            const damage = monster.getBaseDamageStat() * 2;

            characterHealth = characterHealth - damage;

            this.addMessage(monster.name() + ' strikes you in an ambush doing: ' + formatNumber(damage) + ' damage!', 'enemy-action');
        } else if (this.canPlayerAmbush(character, monster)) {
            this.addMessage('You spot the enemy! Now is the time to ambush!', 'player-action');

            let damage = character.base_stat;

            if (isCharacterVoided) {
                damage = character.voided_base_stat;
            }

            damage *= 2;

            monsterHealth = monsterHealth - damage;

            this.addMessage('You strike the enemy in an ambush doing: ' + formatNumber(damage) + ' damage!', 'enemy-action');
        }

        return  {
            monster_health: monsterHealth,
            character_health: characterHealth,
        }
    }

    playerAmbushesPlayer(character: any, monster: Monster, characterHealth: number, monsterHealth: number, isCharacterVoided: boolean): {monster_health: number, character_health: number} {

        if (this.canPlayerAmbush(character, monster)) {
            this.addMessage('You spot the enemy! Now is the time to ambush!', 'player-action');

            let damage = character.base_stat;

            if (isCharacterVoided) {
                damage = character.voided_base_stat;
            }

            damage *= 2;

            monsterHealth = monsterHealth - damage;

            this.addMessage('You strike the enemy in an ambush doing: ' + formatNumber(damage) + ' damage!', 'player-action');
        }else if (this.canEnemyAmbush(monster, character)) {
            this.addMessage('The enemies plotting and scheming comes to fruition!', 'enemy-action');

            const damage = monster.getBaseDamageStat() * 2;

            characterHealth = characterHealth - damage;

            this.addMessage(monster.name() + ' strikes you in an ambush doing: ' + formatNumber(damage) + ' damage!', 'enemy-action');
        }

        return  {
            monster_health: monsterHealth,
            character_health: characterHealth,
        }
    }

    canPlayerAmbush(character: any, monster: Monster): boolean {
        const chance = character.ambush_chance - monster.ambushResistance();

        return random(1, 100) > (100 - 100 * chance);
    }

    canEnemyAmbush(monster: Monster, character: any): boolean {
        const chance = monster.ambushChance() - character.ambush_resistance_chance;

        return random(1, 100) > (100 - 100 * chance);
    }
}
