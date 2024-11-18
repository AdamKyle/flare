import LocationDetails from "../../../game/sections/map/types/location-details";
import NpcDetails from "./deffinitions/npc-details";

export default interface GridOverlayProps {
    map_id: number;
    coordinates: { x: number[] | []; y: number[] | [] };
    mapSrc: string | null;
    locations: LocationDetails[] | [];
    npcs: NpcDetails[] | [];
    updateLocationsAndNpcs: (
        locations: LocationDetails[] | [],
        npcs: NpcDetails[] | [],
    ) => void;
}
