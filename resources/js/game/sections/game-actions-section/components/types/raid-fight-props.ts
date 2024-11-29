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
    character_id: number;
    user_id: number;
    revived: boolean;
    revive: () => void;
    reset_revived: () => void;
    initial_attacks_left: number;
    initial_damage_dealt: number;
    is_raid_boss: boolean;
    manage_elemental_atonement_modal: () => void;
    update_raid_fight: boolean;
    reset_update: () => void;
}
