import {CharacterType} from "../../character/character-type";
import MonsterType from "./monster/monster-type";
import FightSection from "../../../../sections/game-actions-section/components/fight-section";

export default interface FightSectionProps {

    character: CharacterType;

    monster_to_fight: MonsterType;

    is_same_monster: boolean;

    reset_same_monster: () => void;

    set_attack_time_out: (attack_time_out: number) => void;

    reset_revived: () => void;

    character_revived: boolean;

    is_small: boolean;

    is_rank_fight: boolean

    process_rank_fight: (component: FightSection, attackType: string) => void;
}
