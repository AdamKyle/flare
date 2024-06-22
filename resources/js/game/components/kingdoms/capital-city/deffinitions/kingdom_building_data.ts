export interface Building {
    id: number;
    name: string;
    level: number;
    max_level: number;
    current_durability: number;
    max_durability: number;
}

export interface Kingdom {
    kingdom_id: number;
    kingdom_name: string;
    buildings: Building[];
}

export interface CompressedData {
    building_id: number;
    kingdom_id: number;
    kingdom_name: string;
    building_name: string;
    level: number;
    max_level: number;
    current_durability: number;
    max_durability: number;
}
