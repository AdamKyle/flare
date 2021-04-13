import React from 'react';
import {Modal, ModalDialog} from 'react-bootstrap';
import Draggable from 'react-draggable';

class DraggableModalDialog extends React.Component {
  render() {
    return (
      <Draggable handle=".modal-title">
        <div>
          <ModalDialog {...this.props} />
        </div>
      </Draggable>
    );
  }
}

export default class BuildingQueue extends React.Component {

  constructor(props) {
    super(props)

    this.state = {
      building: null,
      queue: null,
    }
  }

  componentDidMount() {
    this.setState({
      building: this.fetchBuilding(),
      queue: this.props.queueData,
    });
  }

  fetchBuilding() {
    const building = this.props.buildings.filter((b) => b.id === this.props.queueData.building_id);

    if (_.isEmpty(building)) {
      return null;
    }

    return building[0];
  }

  cancelUpgrade() {
    axios.post('/api/kingdoms/building-upgrade/cancel', {
      queue_id: this.props.queueData.id
    }).then(() => {
      this.props.close();
    }).catch((result) => {
      console.error(result);
    });
  }

  getIncrease(type) {
    const building = this.state.building;

    if (building.hasOwnProperty('future_' + type + '_increase')) {
      return building['future_' + type + '_increase'];
    }

    return 0;
  }

  upgradeDetails() {
    return (
      <>
        <div className="row mt-2">
          <div className="col-md-6">
            <dl>
              <dt><strong>Wood Gain/hr</strong>:</dt>
              <dd className="text-success">{this.getIncrease('wood')}</dd>
              <dt><strong>Clay Gain/hr</strong>:</dt>
              <dd className="text-success">{this.getIncrease('clay')}</dd>
              <dt><strong>Stone Gain/hr</strong>:</dt>
              <dd className="text-success">{this.getIncrease('stone')}</dd>
              <dt><strong>Iron Gain/hr</strong>:</dt>
              <dd className="text-success">{this.getIncrease('iron')}</dd>
              <dt><strong>Population Gain/hr</strong>:</dt>
              <dd className="text-success">{this.getIncrease('population')}</dd>
            </dl>
          </div>
          <div className="col-md-6">
            <dl>
              <dt><strong>Durability Becomes</strong>:</dt>
              <dd className="text-success">{this.getIncrease('durability')}</dd>
              <dt><strong>Defence Becomes</strong>:</dt>
              <dd className="text-success">{this.getIncrease('defence')}</dd>
              { this.state.building.is_farm ?
                <>
                  <dt><strong>Population Becomes</strong>:</dt>
                  <dd className="text-success">{((this.state.building.level + 1) * 100) + 100}</dd>
                </>
                : null
              }
            </dl>
          </div>
        </div>
      </>
    )
  }

  modalContent() {
    return (
      <>
        <p>{this.state.building.description}</p>
        <hr/>
        <h5 className="mb-2">At Level: {this.state.building.level + 1}</h5>
        {this.upgradeDetails()}
        <hr/>
        <div className="alert alert-warning">
          If you cancel this upgrade, you'll get a perentage of the materials and population back based on
          the amount of time left. If the resources you would get back are less then 10%, you wont be able to
          cancel the building upgrade.
        </div>
      </>
    );
  }

  render() {
    return (
      <Modal
        dialogAs={DraggableModalDialog}
        show={this.props.show}
        onHide={this.props.close}
        aria-labelledby="building-queue-modal"
        dialogClassName="building-queue-management"
        centered
      >
        <Modal.Header closeButton>
          <Modal.Title id="building-queue-management-modal">
            {this.state.building !== null ? this.state.building.name : 'One second ...'}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.building === null ? 'One second' : this.modalContent()
          }
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-danger" onClick={this.props.close}>close</button>
          <button className="btn btn-success" onClick={this.cancelUpgrade.bind(this)}>Cacnel Upgrade</button>
        </Modal.Footer>
      </Modal>
    );
  }
}
