
export interface NPC {
    id: number,
    game_map_id: number,
    name: string,
    real_name: string,
    type: number,
    x_position: number,
    y_position: number,
}

export interface  FactionLoyalty {
    id: number;
    character_id: number;
    faction_id: number;
    is_pledged: boolean;
    faction_loyalty_npcs: FactionLoyaltyNpc[];
}

export interface FactionLoyaltyNpc {
    id:  number;
    faction_loyalty_id: number;
    npc_id: number;
    current_level: number;
    max_level: number;
    next_level_fame: number;
    currently_helping: boolean;
    kingdom_item_defence_bonus: number;
    current_fame: number;
    current_kingdom_item_defence_bonus: number;
    npc: NPC,
    faction_loyalty_npc_tasks: FactionLoyaltyNpcTask,
}

export interface FactionLoyaltyNpcTask {
    id: 1,
    faction_loyalty_id: 1,
    faction_loyalty_npc_id: 1,
    fame_tasks: FameTasks[]|[],
}

export interface FameTasks {
    type: 'bounty' | string;
    monster_id?: number;
    monster_name?: string;
    item_id?: number;
    item_name?: string;
    current_amount: number;
    required_amount: number;
}
