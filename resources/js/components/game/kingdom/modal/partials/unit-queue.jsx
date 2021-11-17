import React, {Fragment} from 'react';
import {Modal, ModalDialog} from 'react-bootstrap';
import Draggable from 'react-draggable';
import UnitData from './unit-data';
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

export default class UnitQueue extends React.Component {

  constructor(props) {
    super(props)

    this.state = {
      unit: null,
      queue: null,
      error: null,
      percentageOfTimeElapsed: 0,
    }
  }

  componentDidMount() {
    this.setState({
      unit: this.fetchUnit(),
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

  fetchUnit() {
    const unit = this.props.units.filter((u) => u.id === this.props.queueData.game_unit_id);

    if (_.isEmpty(unit)) {
      return null;
    }

    return unit[0];
  }

  cancelUpgrade() {
    axios.post('/api/kingdoms/recruit-units/cancel', {
      queue_id: this.state.queue.id
    }).then((result) => {
      this.props.close();
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }
      }

      this.setState({
        error: err.response.data.message,
      });
    });
  }

  getIncrease(type) {
    const building = this.state.unit;

    if (building.hasOwnProperty('future_' + type + '_increase')) {
      return building['future_' + type + '_increase'];
    }

    return 0;
  }

  upgradeDetails() {
    return <UnitData
      kingdom={this.props.kingdom}
      unit={this.state.unit}
      amount={this.state.queue.amount}
      isQueue={true}
    />
  }

  modalContent() {
    return (
      <>
        <p>{this.state.unit.description}</p>
        <hr/>
        <h5 className="mb-2">For Amount: {this.state.queue.amount}</h5>
        {this.upgradeDetails()}
        <hr/>
        <div className="alert alert-warning">
          If you cancel this upgrade, you'll get a perentage of the materials and population back based on
          the amount of time left. If the resources you would get back are less then 10%, you wont be able to
          cancel the unit recruitment.
        </div>
      </>
    );
  }

  paidWithGoldContent() {
    return (
      <Fragment>
        <div className="alert alert-info">
          Should you cancel this at any time that is not above 85% completion, you will get 75% of the gold and population that you
          spent to recruit these units. The remaining 25% is used to cover costs and deserters.
        </div>
        <dl className="mt-2">
          <dt>Percentage of time elapsed</dt>
          <dd>{this.state.percentageOfTimeElapsed}%</dd>
        </dl>
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
            {this.state.unit !== null ? this.state.unit.name : 'One second ...'}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.error !== null ? <div className="alert alert-danger">{this.state.error}</div> : null
          }
          {
            this.state.unit === null ? 'One second' : this.state.queue.gold_paid > 0 ? this.paidWithGoldContent() : this.modalContent()
          }
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-danger" onClick={this.props.close}>close</button>
          <button className="btn btn-success" onClick={this.cancelUpgrade.bind(this)} disabled={this.state.percentageOfTimeElapsed >= 85}>Cancel Upgrade</button>
        </Modal.Footer>
      </Modal>
    );
  }
}
