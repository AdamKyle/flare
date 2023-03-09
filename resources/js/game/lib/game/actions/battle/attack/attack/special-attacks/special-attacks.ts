import {CharacterType} from "../../../../../character/character-type";
import SpecialAttackClasses from "../special-attack-classes";
import AttackType from "../../../../../character/attack-type";
import HammerSmash from "./attacks/hammer-smash";
import TripleAttack from "./attacks/triple-attack";
import DoubleAttack from "./attacks/double-attack";

type BattleMessages = {message: string, type: 'regular' | 'player-action' | 'enemy-action'}[]|[];

export default class SpecialAttacks {

    private character: CharacterType;
    private attackData: AttackType;
    private extraActionChance;
    private monsterHealth: number;
    private characterHealth: number;

    private battleMessage: BattleMessages|[]  = [];


    constructor(character: CharacterType, attackType: AttackType, characterHealth: number, monsterHealth: number) {
        this.character         = character;
        this.attackData        = attackType;
        this.extraActionChance = character.extra_action_chance;
        this.monsterHealth     = monsterHealth;
        this.characterHealth   = characterHealth;
    }

    public doSpecialAttack() {
        this.handleBlackSmith();
        this.tripleAttack();
        this.doubleAttack();
    }

    public getCharacterHealth(): number {
        return this.characterHealth
    }

    public getMonsterHealth(): number {
        return this.monsterHealth;
    }

    public getMessages(): BattleMessages|[] {
        return this.battleMessage;
    }

    protected handleBlackSmith() {
        if (SpecialAttackClasses.isBlackSmith(this.character.class)) {
            const hammerSmash = new HammerSmash();

            this.monsterHealth = hammerSmash.handleAttack(this.character, this.attackData, this.extraActionChance, this.monsterHealth)

            this.battleMessage = hammerSmash.getMessages();
        }
    }

    protected tripleAttack() {
        if (SpecialAttackClasses.isRanger(this.character.class)) {
            const tripleAttack = new TripleAttack();

            this.monsterHealth = tripleAttack.handleAttack(this.character, this.attackData, this.extraActionChance, this.monsterHealth)

            this.battleMessage = tripleAttack.getMessages();
        }
    }

    protected doubleAttack() {
        if (SpecialAttackClasses.isFighter(this.character.class)) {
            const doubleAttack = new DoubleAttack();

            this.monsterHealth = doubleAttack.handleAttack(this.character, this.attackData, this.extraActionChance, this.monsterHealth);

            this.battleMessage = doubleAttack.getMessages();
        }
    }
}
