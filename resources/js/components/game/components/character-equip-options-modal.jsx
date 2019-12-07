import React from 'react';
import Draggable from 'react-draggable';
import {Modal, ModalDialog} from 'react-bootstrap';
import CharacterEquipOptions from './character-equip-options';

class DraggableModalDialog extends React.Component {
  render() {
    return <Draggable handle=".equip-options"><ModalDialog {...this.props} /></Draggable>
  }
}

export default class CharacterEquipOptionsModal extends React.Component {

  constructor(props) {
    super(props);
  }

  callHome(message) {
    this.props.onEquip(message);
  }

  render() {
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
          <Modal.Title><span className="equip-options">Equip Options</span></Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <CharacterEquipOptions itemToEquip={this.props.itemToEquip} equippedItems={this.props.equippedItems} callHome={this.callHome.bind(this)} characterId={this.props.characterId}/>
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
