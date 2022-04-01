import {BattleMessage} from "../types/battle-message-type";
import {random} from "lodash";
import {VoidanceResultType} from "../types/voidance-result-type";
import Monster from "../monster/monster";
import BattleBase from "../battle-base";

export default class Voidance extends BattleBase {

    private character: any;

    private monster: Monster;

    protected voidanceResult: VoidanceResultType;

    constructor(character: any, monster: Monster)  {
        super();

        this.character = character;
        this.monster = monster;

        this.voidanceResult = {
            is_character_voided: false,
            is_monster_voided: false,
            is_character_devoided: false,
            is_monster_devoided: false,
        }
    }

    monsterVoidsCharacter() {

        if (this.monster.canMonsterDevoidPlayer(this.character.devouring_darkness_res)) {
            this.addMessage(this.monster.name() + ' has devoided your voidance! You feel fear start to build.', 'enemy-action');

            this.voidanceResult.is_character_devoided = true;
        }

        if (this.canPlayerDevoidEnemy(this.character.devouring_darkness) && !this.voidanceResult.is_character_devoided) {
            this.addMessage('Magic crackles in the air, the darkness consumes the enemy. They are devoided!', 'regular');

            this.voidanceResult.is_monster_devoided = true;
        }

        if (this.monster.canMonsterVoidPlayer(this.character.devouring_light_res) && !this.voidanceResult.is_monster_devoided) {
            this.addMessage(this.monster.name() + ' has voided your enchantments! You feel much weaker!','enemy-action');

            this.voidanceResult.is_character_voided = true;
        }

        if (this.canPlayerVoidEnemy(this.character.devouring_light) && !this.voidanceResult.is_character_devoided) {
            this.addMessage('The light of the heavens shines through this darkness. The enemy is voided!', 'regular');

            this.voidanceResult.is_monster_voided = true;
        }

        return this.voidanceResult;
    }

    playerVoidsMonster(): VoidanceResultType {
        if (this.canPlayerDevoidEnemy(this.character.devouring_darkness)) {
            this.addMessage('Magic crackles in the air, the darkness consumes the enemy. They are devoided!', 'regular');

            this.voidanceResult.is_monster_devoided = true;
        }

        if (this.monster.canMonsterDevoidPlayer(this.character.devouring_darkness_res) && !this.voidanceResult.is_monster_devoided) {
            this.addMessage(this.monster.name() + ' has devoided your voidance! You feel fear start to build.', 'enemy-action');

            this.voidanceResult.is_character_devoided = true;
        }

        if (this.canPlayerVoidEnemy(this.character.devouring_light) && !this.voidanceResult.is_character_devoided) {
            this.addMessage('The light of the heavens shines through this darkness. The enemy is voided!', 'regular');

            this.voidanceResult.is_monster_voided = true;
        }

        if (this.monster.canMonsterVoidPlayer(this.character.devouring_light_res) && !this.voidanceResult.is_monster_devoided) {
            this.addMessage(this.monster.name() + ' has voided your enchantments! You feel much weaker!','enemy-action');

            this.voidanceResult.is_character_voided = true;
        }


        return this.voidanceResult;
    }

    getVoidanceMessages(): BattleMessage[] | [] {
        return this.getMessages();
    }

    canPlayerDevoidEnemy(devoidChance: number): boolean {

        if (devoidChance >= 1) {
            return true;
        }

        if (devoidChance <= 0.0) {
            return false;
        }

        return  random(1, 100) > (100 - 100 * devoidChance);
    }

    canPlayerVoidEnemy(voidChance: number): boolean {

        if (voidChance >= 1) {
            return true;
        }

        if (voidChance <= 0.0) {
            return false;
        }

        return  random(1, 100) > (100 - 100 * voidChance);
    }
}
