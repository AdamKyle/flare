import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import LoadingModal from '../../../components/loading/loading-modal'

export default class KingdomModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      character: null,
      loading: true,
      errorMessage: null,
    }
  }

  componentDidMount() {
    axios.get('/api/character-location-data/' + this.props.characterId).then((result) => {
      this.setState({
        character: result.data,
        loading: false,
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          this.props.openTimeOutModal()
        }
      }

      this.props.close();
    });
  }

  convertToHex(rgba) {
    return `#${((1 << 24) + (parseInt(rgba[0]) << 16) + (parseInt(rgba[1]) << 8) + parseInt(rgba[2])).toString(16).slice(1)}`
  }

  calculateDistance() {
    const distanceX = Math.pow((this.props.kingdom.x_position - this.state.character.x_position), 2);
    const distanceY = Math.pow((this.props.kingdom.y_position - this.state.character.y_position), 2);

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

  disabled() {
    const doesNotHaveGold = this.cost() > this.state.character.gold;

    return this.props.disableMapButtons() || doesNotHaveGold;
  }

  teleport() {
    axios.post('/api/map/teleport/' + this.props.characterId, {
      x: this.props.kingdom.x_position,
      y: this.props.kingdom.y_position,
      cost: this.cost(),
      timeout: this.time(),
    }).then(() => {
      this.props.close();
    }).catch((error) => {
      this.props.close();

      if (error.hasOwnProperty('response')) {
        if (error.response.status === 429) {
          return this.props.openTimeOutModal();
        }

        if (error.response.status === 401) {
          return location.reload();
        }

        this.setState({
          errorMessage: error.response.data.message
        });
      }
    });
  }

  render() {
    if (this.state.loading) {
      return (
        <LoadingModal
          loadingText="Fetching location data ..."
          show={this.props.show}
          close={this.props.close}
        />
      );
    }

    return (
      <Modal show={this.props.show} onHide={this.props.close}>
        <Modal.Header closeButton style={{backgroundColor: this.convertToHex(this.props.kingdom.color)}}>
          <Modal.Title style={{color: '#fff'}}>{this.props.kingdom.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.errorMessage ?
              <div className="alert alert-danger mt-2 mb-3">
                {this.state.errorMessage}
              </div>
              : null
          }
          <dl className="mt-2">
            <dt><strong>Location (X/Y)</strong>:</dt>
            <dd>
              {this.props.kingdom.x_position}/{this.props.kingdom.y_position}
            </dd>
            <dt><strong>Teleport Distance</strong>:</dt>
            <dd>{this.calculateDistance()}</dd>
            <dt><strong>Cost</strong>:</dt>
            <dd>{this.cost()}</dd>
            <dt><strong>Timeout</strong>:</dt>
            <dd>{this.time()} Minutes</dd>
          </dl>
          {
            this.props.disableMapButtons() ?
              <p className="mt-3 text-center text-danger">
                You cannot currently telport to this kingdom.
              </p>
              : null
          }
          {
            this.cost() > this.state.character.gold ?
              <p className="mt-3 text-center text-danger">
                You don't have the gold to teleport.
              </p>
              : null
          }
        </Modal.Body>
        <Modal.Footer>
          <Button variant="primary" onClick={this.teleport.bind(this)} disabled={this.disabled()}>
            Teleport To Kingdom
          </Button>
          <Button variant="danger" onClick={this.props.close}>
            Close
          </Button>
        </Modal.Footer>
      </Modal>
    );
  }
}
