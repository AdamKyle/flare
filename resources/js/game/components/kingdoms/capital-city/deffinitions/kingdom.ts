import Building from "./building";

export default interface Kingdom {
    character_id: number;
    id: number;
    kingdom_name: string;
    map_name: string;
    kingdom_id: number;
    buildings: Building[];
}
