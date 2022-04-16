import React, {Fragment} from 'react';
import {Modal, ModalDialog} from 'react-bootstrap';
import Draggable from 'react-draggable';
import moment from "moment";

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
      percentageOfTimeElapsed: 0,
      loading: false,
    }
  }

  componentDidMount() {
    this.setState({
      building: this.fetchBuilding(),
      queue: this.props.queueData,
    });

    this.interval = setInterval(() => {
      const now         = moment();
      const completedAt = moment(this.state.queue.completed_at);
      const startedAt   = moment(this.state.queue.started_at);

      const totalTime   = completedAt.diff(startedAt, 'minutes');
      const timeElapsed = now.diff(startedAt, 'minutes');

      this.setState({
        percentageOfTimeElapsed: Math.ceil(timeElapsed/totalTime * 100)
      })

      return ;
    }, 1000)
  }

  componentWillUnmount() {
    clearInterval(this.interval);
  }

  fetchBuilding() {
    const building = this.props.buildings.filter((b) => b.id === this.props.queueData.building_id);

    if (_.isEmpty(building)) {
      return null;
    }

    return building[0];
  }

  cancelUpgrade() {
    this.setState({loading: true});
    axios.post('/api/kingdoms/building-upgrade/cancel', {
      queue_id: this.props.queueData.id
    }).then(() => {
      this.setState({loading: false});
      this.props.close();
    }).catch((err) => {
      this.setState({loading: false});
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }
      }
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
          If you cancel this upgrade, you'll get a percentage of the materials and population back based on
          the amount of time left. If the resources you would get back are less than 10%, you wont be able to
          cancel the building upgrade.
        </div>
      </>
    );
  }

  modelPaidWithGoldContent() {
    return (
      <Fragment>
        <p>{this.state.building.description}</p>
        <hr/>
        <p>
          Depending on time left, you'll get a percentage of the money spent back. Ie, if there is 50% of the time left, you will get 50%
          of the money back. <strong>HOWEVER, you will not get any population back</strong>. The amount of gold you get back is a
          percentage of the building level BEFORE any additional population was purchased. If money as used to purchase additional people,
          consider it gone.
        </p>
        <p>
          If the time elapsed is greater than 85% then you cannot cancel the process and must allow it to finish. This would be too wasteful
          of both gold and people.
        </p>
        <p>
          These values below will update in real time, the longer you leave this modal open the less gold you will get back.
        </p>
        <dl className="mt-2">
          <dt>Percentage of time elapsed</dt>
          <dd>{this.state.percentageOfTimeElapsed}%</dd>
          <dt>Percentage of gold to be given back<sup>*</sup></dt>
          <dd>{100 - this.state.percentageOfTimeElapsed}%</dd>
        </dl>
        <p className="mt-3"><sup>*</sup> Remember you get <strong>no</strong> population back. Just gold.</p>
      </Fragment>
    )
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
            {this.state.building !== null ? this.state.building.name : 'One second...'}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.building === null ? 'One second' : this.state.queue.paid_with_gold ? this.modelPaidWithGoldContent() : this.modalContent()
          }
          {
            this.state.loading ?
              <div className="progress loading-progress kingdom-loading " style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
              : null
          }
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-danger" onClick={this.props.close}>close</button>
          <button className="btn btn-success" onClick={this.cancelUpgrade.bind(this)} disabled={this.state.percentageOfTimeElapsed >= 85 && this.state.queue.paid_with_gold}>Cancel Upgrade</button>
        </Modal.Footer>
      </Modal>
    );
  }
}
