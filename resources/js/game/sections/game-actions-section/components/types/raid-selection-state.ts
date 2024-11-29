import ElementalAtonement from "../../../../components/crafting/gem-crafting/deffinitions/elemental-atonement";

export default interface RaidSelectionState {
    is_loading: boolean;
    is_fighting: boolean;
    monster_current_health: number;
    monster_max_health: number;
    character_current_health: number;
    character_max_health: number;
    monster_name: string;
    selected_raid_monster_id: number;
    revived: boolean;
    raid_boss_attacks_left: number;
    raid_boss_damage_dealt: number;
    is_raid_boss: boolean;
    open_elemental_atonement: boolean;
    elemental_atonement: ElementalAtonement | object;
    highest_element: string | null;
    update_raid_fight: boolean;
}
