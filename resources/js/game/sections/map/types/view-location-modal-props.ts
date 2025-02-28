import LocationDetails from "./location-details";

export default interface ViewLocationModalProps {
    player_kingdom_id: number | null;

    enemy_kingdom_id: number | null;

    npc_kingdom_id: number | null;

    location: LocationDetails | null;

    character_id: number;

    handle_close: () => void;

    can_move: boolean;

    is_automation_running: boolean;

    is_dead: boolean;
}
