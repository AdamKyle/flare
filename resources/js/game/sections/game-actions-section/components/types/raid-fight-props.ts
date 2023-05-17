export default interface RaidFightProps {

    character_current_health: number;
    character_max_health: number;
    monster_current_health: number;
    monster_max_health: number;
    monster_name: string;
    is_dead: boolean;
    can_attack: boolean;
    monster_id: number;
    is_small: boolean;
    character_name: string;
}