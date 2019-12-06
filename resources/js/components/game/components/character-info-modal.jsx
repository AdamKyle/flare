import React from 'react';
import Draggable from 'react-draggable';
import {Modal, ModalDialog} from 'react-bootstrap';
import CharacterSheet from './character-sheet';
import CharacterInventoryModal from './character-inventory-modal';

class DraggableModalDialog extends React.Component {
  render() {
    return <Draggable handle=".character-sheet"><ModalDialog {...this.props} /></Draggable>
  }
}

export default class CharacterInfoModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      characterSheet: null,
      isLoading: true,
      showInventory: false,
    }

    this.characterSheet = Echo.private('update-character-sheet-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/character-sheet/' + this.props.characterId)
      .then((result) => {
        this.setState({
          characterSheet: result.data.sheet.data,
          isLoading: false,
        });
      });

    this.characterSheet.listen('Flare.Events.UpdateCharacterSheetBroadcastEvent', (event) => {
      this.setState({
        characterSheet: event.characterSheet.data,
      });
    });
  }

  openInventory() {
    this.setState({
      showInventory: true,
    });
  }

  closeInventory() {
    this.setState({
      showInventory: false,
    });
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
          <Modal.Title><span className="character-sheet">Character Sheet</span></Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {this.state.isLoading ? 'Please wait ...' : <CharacterSheet sheet={this.state.characterSheet} />}
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-success force-left" type="button" onClick={this.openInventory.bind(this)}>
            Inventory
          </button>
          <button className="btn btn-primary" type="button" onClick={this.props.onClose}>
            Close
          </button>
        </Modal.Footer>

        <CharacterInventoryModal show={this.state.showInventory} onClose={this.closeInventory.bind(this)} characterId={this.props.characterId} userId={this.props.userId} />
      </Modal>
    );
  }
}
