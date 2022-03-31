import {random} from "lodash";
import Monster from "../../../../monster/monster";
import {formatNumber} from "../../../../../../format-number";
import {BattleMessage} from "../../../../types/battle-message-type";

export default class CounterHandler {

    private battle_messages: BattleMessage[] = [];

    private character_health: number = 0;

    private monster_health: number = 0;

    setCharacterHealth(characterHealth: number): CounterHandler {
        this.character_health = characterHealth;

        return this;
    }

    setEnemyHealth(enemyHealth: number): CounterHandler {
        this.monster_health = enemyHealth;

        return this;
    }

    enemyCountersPlayers(character: any, monster: any, isCharacterVoided: boolean) {
        if (this.canEnemyCounter(character, monster)) {
            this.addMessage('The enemy swings around in a counter move!', 'enemy-action');

            const damage = (new Monster(monster)).attack() * 0.05;

            this.character_health = this.character_health - damage;

            this.addMessage(monster.name + ' strikes, in a counter move, you for: ' + formatNumber(damage) + ' weapon damage!', 'enemy-action');
        }

        if (this.character_health > 0 && this.canCounterAgain()) {
            this.addMessage('You manage to counter the enemies counter', 'regular');

            let damage = character.weapon_attack;

            if (isCharacterVoided) {
                damage = character.voided_weapon_attack;
            }

            damage = damage * 0.025;

            this.monster_health = this.monster_health - damage;

            this.addMessage('Lashing out at the enemy you do: ' + formatNumber(damage) + ' weapon damage!', 'player-action');
        }
    }

    playerCountersEnemy(character: any, monster: any, isCharacterVoided: boolean) {
        if (this.canPlayerCounter(character, monster)) {
            this.addMessage('You swing around to counter the enemy!', 'regular');

            let damage = character.weapon_atack;

            if (isCharacterVoided) {
                damage = character.voided_weapon_attack
            }

            damage = damage * 0.05

            this.monster_health = this.monster_health - damage;

            this.addMessage('Your counter lashes out for: ' + formatNumber(damage) + ' weapon damage!', 'player-action');
        }

        if (this.monster_health > 0 && this.canCounterAgain()) {
            this.addMessage('The enemy manages to counter your counter!', 'enemy-action');

            let damage = (new Monster(monster)).attack();

            damage = damage * 0.025;

            this.character_health = this.character_health - damage;

            this.addMessage('Lashing out at you, the enemy does: ' + formatNumber(damage) + ' weapon damage!', 'enemy-action');
        }
    }

    getState() {
        return {
            character_health: this.character_health > 0 ? this.character_health : 0,
            monster_health: this.monster_health > 0 ? this.monster_health : 0,
        }
    }

    isEnemyDead() {
        return this.monster_health <= 0;
    }

    isPlayerDead() {
        return this.character_health <= 0;
    }

    getMessages(): BattleMessage[] {
        return this.battle_messages;
    }

    addMessage(message: string, type: 'regular' | 'player-action' | 'enemy-action'): void {
        this.battle_messages.push({
            message: message,
            type: type,
        })
    }

    canPlayerCounter(character: any, monster: any) {
        const chance = character.counter_chance - monster.counter_resistance;

        if (chance <= 0) {
          return false;
        }

        return random(1, 100) > (100 - 100 * chance);
    }

    canEnemyCounter(character: any, monster: any) {
        const chance = monster.counter_chance - character.counter_resistance;

        if (chance <= 0) {
          return false;
        }

        return random(1, 100) > (100 - 100 * chance);
    }

    canCounterAgain() {
        return random(1, 100) > (100 - 100 * 0.02);
    }

}
