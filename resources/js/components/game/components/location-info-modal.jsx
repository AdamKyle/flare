import React from 'react';
import Draggable from 'react-draggable';
import {Modal, ModalDialog} from 'react-bootstrap';
import LocationInfo from './location-info';

class DraggableModalDialog extends React.Component {
  render() {
    return <Draggable handle=".location-info"><ModalDialog {...this.props} /></Draggable>
  }
}

export default class CharacterInfoModal extends React.Component {

  constructor(props) {
    super(props);

  }

  render() {
    const location = this.props.location;

    if (location === null) {
      return null;
    }

    return(
      <Modal
        backdrop={'static'}
        dialogAs={DraggableModalDialog}
        show={this.props.show}
        onHide={this.props.onClose}
        animation={true}
        size="lg"
        aria-labelledby="contained-modal-title-vcenter"
        centered
      >
        <Modal.Header closeButton>
          <Modal.Title><span className="location-info">{location.name}</span></Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <LocationInfo location={location} />
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-primary" type="button" onClick={this.props.onClose}>
            Close
          </button>
        </Modal.Footer>
      </Modal>
    );
  }
}
