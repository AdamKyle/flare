import React from "react";
import Dialogue from "../../../../../../components/ui/dialogue/dialogue";
import LocationDetailsProps
    from "../../../../../../lib/game/types/map/modals/components/view-location/location-details-props";
import LocationInformation from '../../../../../components/locations/modals/location-details';

export default class LocationDetails extends React.Component<LocationDetailsProps, any> {
    constructor(props: LocationDetailsProps) {
        super(props);
    }

    buildTitle(): string {
        const location = this.props.location;

        return location.name + ' (X/Y): ' + location.x + '/' + location.y;
    }

    render() {
        return (
            <Dialogue is_open={true}
                      handle_close={this.props.handle_close}
                      title={this.buildTitle()}
            >

                <LocationInformation location={this.props.location} />
            </Dialogue>
        )
    }
}
