import LocationDetails from "../../../game/sections/map/types/location-details";
import NpcDetails from "./deffinitions/npc-details";

export default interface MapManagerState {
    loading: boolean;
    imgSrc: string | null;
    coordinates: { x: number[] | []; y: number[] | [] };
    locations: LocationDetails[] | [];
    npcs: NpcDetails[] | [];
    error_message: string | null;
}
