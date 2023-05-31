export default interface RaidSelectionState {
    
    is_loading: boolean;
    is_fighting: boolean;
    monster_current_health: number,
    monster_max_health: number;
    character_current_health: number;
    character_max_health: number;
    monster_name: string;
    selected_raid_monster_id: number;
    revived: boolean;
    raid_boss_attacks_left: number;
    is_raid_boss: boolean;
}