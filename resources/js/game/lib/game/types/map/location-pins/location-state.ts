import LocationDetails from "../../../map/types/location-details";

export default interface LocationState {

    open_location_modal: boolean

    location?: LocationDetails | null
}
