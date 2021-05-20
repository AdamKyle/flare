import React from 'react';
import Card from '../../components/templates/card';

export default class Teleport extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      x: this.props.teleportLocations.x,
      y: this.props.teleportLocations.y,
      current_x: this.props.currentX,
      current_y: this.props.currentY,
      selected_x: this.props.currentX,
      selected_y: this.props.currentY,
      errorMessage: null,
    }
  }

  componentDidUpdate(prevProps, prevState) {
    if (this.state.current_x !== this.props.currentX || this.state.current_y !== this.props.currentY) {
      this.setState({
        current_x: this.props.currentX,
        current_y: this.props.currentY,
        selected_x: this.props.currentX,
        selected_y: this.props.currentY,
      });
    }
  }

  closeDetails() {
    this.props.openTeleportDetails(false);
  }

  fetchXLocations() {
    const xLocations = [];

    this.state.x.forEach((position) => {
      xLocations.push(
        <option value={position} key={'position-x-' + position}>{position}</option>
      )
    });

    return xLocations;
  }

  fetchYLocations() {
    const yLocations = [];

    this.state.y.forEach((position) => {
      yLocations.push(
        <option value={position} key={'position-y-' + position}>{position}</option>
      )
    });

    return yLocations;
  }

  handleX(e) {
    this.setState({
      selected_x: e.target.value
    });
  }

  handleY(e) {
    this.setState({
      selected_y: e.target.value
    });
  }

  calculateDistance() {
    if (this.state.selected_x === '' && this.state.selected_y === '') {
      return 0;
    }

    const distanceX = Math.pow((this.state.selected_x - this.state.current_x), 2);
    const distanceY = Math.pow((this.state.selected_y - this.state.current_y), 2);

    let distance = distanceX + distanceY;
    distance = Math.sqrt(distance);

    if (isNaN(distance)) {
      return 0;
    }

    return Math.round(distance);
  }

  time() {
    let time = Math.round(this.calculateDistance() / 60);

    if (time === 0) {
      return 1;
    }

    return time;
  }

  cost() {
    return this.time() * 1000;
  }

  samePositionAsSelected() {
    return this.state.current_x === this.state.selected_x && this.state.current_y === this.state.selected_y;
  }

  teleport() {
    axios.post('/api/map/teleport/' + this.props.characterId, {
      x: this.state.selected_x,
      y: this.state.selected_y,
      cost: this.cost(),
      timeout: this.time(),
    }).then(() => {
      this.closeDetails();
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload();
        }
      }

      this.setState({
        errorMessage: error.response.data.message
      });
    });
  }

  render() {
    return (
      <Card
        OtherCss="p-3"
        cardTitle="Teleport"
        close={this.closeDetails.bind(this)}
      >
        {this.state.errorMessage !== null ?
          <div className="alert alert-danger">{this.state.errorMessage}</div>
          : null}
        <div className="form-row">
          <div className="form-group col-md-6">
            <label htmlFor="x-position">X Position</label>
            <select className="form-control" id="x-position" onChange={this.handleX.bind(this)}
                    value={this.state.selected_x}>
              {this.fetchXLocations()}
            </select>
          </div>
          <div className="form-group col-md-6">
            <label htmlFor="x-position">Y Position</label>
            <select className="form-control" id="x-position" onChange={this.handleY.bind(this)}
                    value={this.state.selected_y}>
              {this.fetchYLocations()}
            </select>
          </div>
        </div>
        {this.state.selected_x !== '' && this.state.selected_y !== ''
          ?
          <>
            <div className="row">
              <div className="col-md-12">
                <hr/>
                <strong>Total Disatnce: </strong> {this.samePositionAsSelected() ? 0 : this.calculateDistance()} <br/>
                <strong>Total Cost: </strong> {this.samePositionAsSelected() ? 0 : this.cost()}<br/>
                <strong>Timeout (Minutes): </strong> {this.samePositionAsSelected() ? 0 : this.time()}<br/>
              </div>
            </div>
            <div className="row">
              <div className="col-md-12">
                <hr/>
                {this.samePositionAsSelected() ? null :
                  <button className="btn btn-primary float-left" onClick={this.teleport.bind(this)}>Teleport</button>
                }
              </div>
            </div>
          </>
          : null
        }
      </Card>
    );
  }
}
