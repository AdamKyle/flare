import CharacterMapDetails from "../../types/character-map-details";
import LocationDetails from "../../types/location-details";

export default interface MapData {
    can_move: boolean;

    can_move_again_at: string | null;

    can_settle_kingdom: boolean;

    celestial_id: number | null;

    character_map: CharacterMapDetails;

    characters_on_map: number;

    coordinates: { x: [number]; y: [number] };

    locations: LocationDetails[] | [];

    lockedLocationType: null;

    map_url: string;

    my_kingdoms: [];

    npc_kingdoms: [];

    other_kingdoms: [];
}
