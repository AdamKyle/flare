import MapData from "../request-types/MapData";

export default interface DirectionalMovementProps {

    update_map_state: (data: MapData, callback?: () => void) => void;

    character_position: {x: number, y: number};

    map_position: {x: number, y: number};

    view_port: number;

    is_dead: boolean;

    is_automation_running: boolean;

    character_id: number;

    map_id: number;
}
