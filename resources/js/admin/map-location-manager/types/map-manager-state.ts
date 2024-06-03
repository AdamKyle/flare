import LocationDetails from "../../../game/sections/map/types/location-details";

export default interface MapManagerState {
    loading: boolean;
    imgSrc: string | null;
    coordinates: { x: number[] | []; y: number[] | [] };
    locations: LocationDetails[] | [];
}
