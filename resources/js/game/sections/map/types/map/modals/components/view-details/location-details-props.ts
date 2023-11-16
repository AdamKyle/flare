import LocationDetails from "../../../../location-details";

export default interface LocationDetailsProps {
    location: LocationDetails;

    handle_close: () => void;
}
