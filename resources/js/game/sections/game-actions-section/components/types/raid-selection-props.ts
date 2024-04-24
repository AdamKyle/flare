import RaidMonsterType from "../../../../lib/game/types/actions/monster/raid-monster-type";

export default interface RaidSelectionProps {
    raid_monsters: RaidMonsterType[] | [];

    character_id: number;

    character_current_health: number;

    user_id: number;

    can_attack: boolean;

    is_dead: boolean;

    close_monster_section?: () => void;

    children?: React.ReactNode;

    is_small: boolean;

    character_name: string;
}
