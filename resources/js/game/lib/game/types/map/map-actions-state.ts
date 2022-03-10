import LocationDetails from "../../map/types/location-details";

export default interface MapActionsState {

    is_movement_disabled: boolean;

    open_teleport_modal: boolean;

    location: LocationDetails | null;

    player_kingdom_id: number;

    show_location_details: boolean;
}
