import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import LoadingModal from '../../../components/loading/loading-modal'

export default class EnemyKingdomModal extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    return (
      <Modal show={this.props.show} onHide={this.props.close}>
        <Modal.Header closeButton>
          <Modal.Title>Enemy Kingdom</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <dl>
            <dt>Belongs to:</dt>
            <dd>{this.props.kingdom.character_name}</dd>
            <dt>X/Y:</dt>
            <dd>{this.props.kingdom.x_position}/{this.props.kingdom.y_position}</dd>
          </dl>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="danger" onClick={this.props.close}>
            Close
          </Button>
        </Modal.Footer>
      </Modal>
    );
  }
}
