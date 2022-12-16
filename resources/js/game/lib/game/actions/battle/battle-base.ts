import {BattleMessage} from "./types/battle-message-type";
import CounterHandler from "./attack/attack/attack-types/ambush-and-counter/CounterHandler";
import {formatNumber} from "../../format-number";

export default class BattleBase {

    protected battle_messages: BattleMessage[];

    protected character_is_dead: boolean = false;

    protected monster_is_dead: boolean = false;

    constructor() {
        this.battle_messages = [];
    }

    getMessages(): BattleMessage[] {
        return this.battle_messages;
    }

    resetMessages() {
        this.battle_messages = [];
    }

    getIsCharacterDead() {
        return this.character_is_dead;
    }

    addMessage(message: string, type: 'regular' | 'player-action' | 'enemy-action'): void {
        this.battle_messages.push({
            message: message,
            type: type,
        })
    }

    mergeMessages(messagesToMerge: BattleMessage[]) {
        this.battle_messages = [...this.battle_messages, ...messagesToMerge];
    }

    handleClassSpecialAttackEquipped(character: any, monsterHealth: number) {
        
        if (character.special_damage.length == 0) {
            return monsterHealth;
        }

        if (character.special_damage.required_attack_type !== 'any') {

            if (character.special_damage.required_attack_type !== character.attack_type) {
                return monsterHealth;
            }
        }

        monsterHealth = monsterHealth - character.special_damage.damage;

        this.addMessage('Your class special: ' + character.special_damage.name + ' fires off and you do: ' + formatNumber(character.special_damage.damage) + ' damage to the enemy!', "player-action");

        return monsterHealth > 0 ? monsterHealth : 0
    }

    handleCounter(character: any, monster: any, characterHealth: number, monsterHealth: number, type: 'enemy' | 'player', isCharacterVoided: boolean) {
        let counter = new CounterHandler();

        counter = counter.setCharacterHealth(characterHealth).setEnemyHealth(monsterHealth);

        if (type === 'enemy') {
            counter.enemyCountersPlayers(character, monster, isCharacterVoided);

            this.mergeMessages(counter.getMessages());

            if (counter.isEnemyDead()) {
                this.addMessage('Your counter of the enemies counter has eviscerated them!', 'enemy-action');

                this.monster_is_dead = true;
            }

            if (counter.isPlayerDead()) {
                this.addMessage('The enemies counter has eviscerated you.', 'enemy-action');

                this.character_is_dead = true;
            }

            return counter.getState();
        }
    }
}
