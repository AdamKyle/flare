import MonsterType from "../../../../../lib/game/types/actions/monster/monster-type";
import {CharacterType} from "../../../../../lib/game/character/character-type";
import CharacterStatusType from "../../../../../lib/game/character/character-status-type";
import React from "react";

export default interface MonsterActionsProps {
    character: CharacterType;

    character_statuses: CharacterStatusType;

    close_monster_section?: () => void;

    monsters: MonsterType[];

    is_small: boolean;

    children?: React.ReactNode;

    is_rank_fights: boolean;

    total_ranks: number;
}
