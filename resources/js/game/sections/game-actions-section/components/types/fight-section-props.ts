import { CharacterType } from "../../../../lib/game/character/character-type";
import MonsterType from "../../../../lib/game/types/actions/monster/monster-type";
import FightSection from "../fight-section";

export default interface FightSectionProps {
    character: CharacterType;

    monster_to_fight: MonsterType;

    is_same_monster: boolean;

    reset_same_monster: () => void;

    set_attack_time_out: (attack_time_out: number) => void;

    reset_revived: () => void;

    character_revived: boolean;

    is_small: boolean;
}
