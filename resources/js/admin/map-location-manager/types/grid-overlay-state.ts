import LocationDetails from "../../../game/sections/map/types/location-details";

export default interface GridOverlayState {
    coordinates: { x: number; y: number };
    hoveredGridCell: { x: number | null; y: number | null };
    showTooltip: boolean;
    tooltipPosition: string;
    snapped: boolean;
    showModal: boolean;
}
