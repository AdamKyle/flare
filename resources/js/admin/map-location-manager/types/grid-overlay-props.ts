import LocationDetails from "../../../game/sections/map/types/location-details";

export default interface GridOverlayProps {
    coordinates: { x: number[] | []; y: number[] | [] };
    mapSrc: string | null;
    locations: LocationDetails[] | [];
}
