import Unit from "./unit";

export interface KingdomWithUnitRequests {
    kingdom_id: number;
    kingdom_name: string;
    map_name: string;
    unit_requests: Unit[];
    status: string;
    total_time: number;
}
