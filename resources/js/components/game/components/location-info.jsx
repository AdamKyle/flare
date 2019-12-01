import React from 'react';

export default class LocationInfo extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const location = this.props.location;

    if (location == null) {
      return null;
    }

    return (
      <div>
        <p>{'Coordinates: ' + location.x + '/' + location.y}</p>
        <div className="text-center mb-2">
          <i>{location.description}</i>
        </div>
      </div>
    );
  }
}
