import LocationDetails from "../../../../../map/types/location-details";

export default interface LocationDetailsProps {
    location: LocationDetails;

    handle_close: () => void;
}
