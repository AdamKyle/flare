import MapData from "../lib/request-types/MapData";
import LocationDetails from "./location-details";

export default interface DirectionalMovementProps {
    update_map_state: (data: MapData, callback?: () => void) => void;

    character_position: { x: number; y: number };

    map_position: { x: number; y: number };

    view_port: number;

    is_dead: boolean;

    is_automation_running: boolean;

    is_delve_running: boolean;

    locations: LocationDetails[] | null;

    character_id: number;

    map_id: number;

    can_move: boolean;
}
