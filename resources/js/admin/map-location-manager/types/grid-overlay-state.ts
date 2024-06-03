import LocationDetails from "../../../game/sections/map/types/location-details";

export default interface GridOverlayState {
    coordinates: { x: number; y: number };
    showTooltip: boolean;
    tooltipPosition: string;
    snapped: boolean;
}
