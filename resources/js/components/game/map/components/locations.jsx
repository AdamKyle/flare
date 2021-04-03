import React from 'react';
import PortPin from './pins/port-pin';
import LocationPin from './pins/location-pin';
import LocationInfoModal from './modals/location-info-modal';

export default class Location extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      location: null,
      showLocationInfo: false,
    }
  }

  openLocationDetails(locationInformation) {
    this.setState(locationInformation);
  }

  closeLocationDetails() {
    this.setState({
      showLocationInfo: false,
      location: null,
    });
  }

  renderLocations() {
    return this.props.locations.map((location) => {
      if (location.is_port) {
        return (
          <PortPin
            key={'port_id_' + location.id}
            locations={this.props.locations}
            location={location}
            openLocationDetails={this.openLocationDetails.bind(this)}
          />
        );

      } else {
        return (
          <LocationPin
            key={'location_id_' + location.id}
            locations={this.props.locations}
            location={location}
            openLocationDetails={this.openLocationDetails.bind(this)}
          />
        );
      }
    });
  }

  render() {
    return (
      <>
        {this.renderLocations()}
        <LocationInfoModal
          show={this.state.showLocationInfo}
          onClose={this.closeLocationDetails.bind(this)}
          location={this.state.location}
        />
      </>
    )
  }
}
