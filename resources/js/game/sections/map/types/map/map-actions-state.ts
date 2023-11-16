import LocationDetails from "../location-details";

export default interface MapActionsState {

    is_movement_disabled: boolean;

    open_teleport_modal: boolean;

    open_set_sail_modal: boolean;

    location: LocationDetails | null;

    player_kingdom_id: number | null;

    enemy_kingdom_id: number | null;

    npc_kingdom_id: number | null;

    show_location_details: boolean;

    show_traverse: boolean;

    show_conjuration: boolean;

    open_settle_kingdom_modal: boolean;
}
