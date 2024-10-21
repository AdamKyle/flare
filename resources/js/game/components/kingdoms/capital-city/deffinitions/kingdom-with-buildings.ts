import Building from "./building";

export default interface KingdomWithBuildings {
    character_id: number;
    id: number;
    kingdom_name: string;
    map_name: string;
    kingdom_id: number;
    buildings: Building[];
}
