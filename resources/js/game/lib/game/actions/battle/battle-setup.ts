import Monster from "./monster/monster";
import Voidance from "./voidance/voidance";
import {BattleMessage} from "./types/battle-message-type";
import {VoidanceResultType} from "./types/voidance-result-type";

export default class BattleSetUp {

    private character: any;

    private monster: any;

    private voidanceResult: VoidanceResultType;

    private battleMessages: BattleMessage[];

    private modified_monster: Monster|null;

    constructor(character: any, monster: any) {

        this.character = character;
        this.monster   = monster;

        this.modified_monster = null;

        this.voidanceResult = {
            is_character_voided: false,
            is_monster_voided: false,
            is_character_devoided: false,
            is_monster_devoided: false,
        }

        this.battleMessages = [];
    }

    setUp() {
        let monster          = new Monster(this.monster);
        const voidance       = new Voidance(this.character, monster);

        switch(monster.plane()) {
            case 'Purgatory':
                this.voidanceResult = voidance.monsterVoidsCharacter();
                break;
            default:
                this.voidanceResult = voidance.playerVoidsMonster();
                // Player Voids
        }

        this.battleMessages = voidance.getVoidanceMessages();

        this.reduceMonster(monster);

        this.modified_monster = monster;
    }

    reduceMonster(monster: Monster) {
        if (!this.voidanceResult.is_character_voided) {
            monster.reduceStats(this.character);
            monster.reduceSkills(this.character.skill_reduction);
            monster.reduceResistances(this.character.resistance_reduction);

            this.monster = monster.getMonster();

            this.battleMessages = [...this.battleMessages, ...monster.getMessages()]
        }
    }

    getVoidanceResult(): VoidanceResultType {
        return this.voidanceResult;
    }

    getMessages(): BattleMessage[] {
        return this.battleMessages;
    }

    getMonsterObject(): Monster {
        return new Monster(this.monster);
    }

    getMonster() {
        return this.modified_monster?.getMonster();
    }

    getMonsterHealth(): number {
        if (this.modified_monster === null) {
            return 0;
        }

        return this.modified_monster.health()
    }
}
