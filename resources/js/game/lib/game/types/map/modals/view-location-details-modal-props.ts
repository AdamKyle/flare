import LocationDetails from "../../../map/types/location-details";
import KingdomDetails from "../../../map/types/kingdom-details";

export default interface ViewLocationDetailsModalProps {

    location: LocationDetails | null;

    kingdom_id: number | null;

    character_id: number;

    close_modal: () => void;
}
