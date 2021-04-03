import React from 'react';

export default class LocationPin extends React.Component {

  constructor(props) {
    super(props);
  }

  openLocationInformation(event) {
    const location = this.props.locations.filter(
      l => l.id === parseInt(event.target.getAttribute('data-location-id'))
    )[0];

    this.props.openLocationDetails({
      showLocationInfo: true,
      location: location,
    });
  }

  render() {
    return (
      <div
        key={this.props.location.id}
        data-location-id={this.props.location.id}
        className="location-x-pin"
        style={{top: this.props.location.y, left: this.props.location.x}}
        onClick={this.openLocationInformation.bind(this)}>
      </div>
    )
  }
}
