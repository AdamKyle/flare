import React from 'react';
import {Modal, ModalDialog} from 'react-bootstrap';

export default class KingdomUnitRecall extends React.Component {

  constructor(props) {
    super(props);
  }

  movementType() {
    const unitsInMovement = this.props.unitsToRecall;

    if (unitsInMovement.is_attacking) {
      return 'Attacking';
    }

    if (unitsInMovement.is_returning) {
      return 'Returning';
    }

    if (unitsInMovement.is_recalled) {
      return 'Recalled';
    }

    if (unitsInMovement.is_moving) {
      return 'Moving';
    }
  }

  render() {
    return (
      <Modal
        show={this.props.show}
        onHide={this.props.close}
        aria-labelledby="kingdom-management-modal"
        backdrop="static"
      >
        <Modal.Header closeButton>
          <Modal.Title id="kingdom-management-modal">
            Unit Recall
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <dl>
            <dt><strong>From Kingdom</strong>:</dt>
            <dd>{this.props.unitsToRecall.from_kingdom_name}</dd>
            <dt><strong>To Kingdom</strong>:</dt>
            <dd>{this.props.unitsToRecall.to_kingdom_name}</dd>
            <dt><strong>Movement Type</strong>:</dt>
            <dd>{this.movementType()}</dd>
          </dl>
          <hr />
          <div className="alert alert-warning">
            Canceling unit movement can only be done if they are moving or attacking. You cannot recal if they are returning.
            You also cannot recall if they are about to arrive at their destination.
          </div>
          <hr />
          <div classname="clearfix">
            <button className="btn btn-danger float-right" onClick={this.props.close}>Close</button>
            <button className="btn btn-primary float-right mr-2" disabled={this.props.unitsToRecall.is_returning || this.props.unitsToRecall.is_moving}>Recall</button>
          </div>
        </Modal.Body>
      </Modal>
    );
  }
}
