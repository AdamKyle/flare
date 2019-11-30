import React   from 'react';
import Draggable from 'react-draggable';
import {Modal, ModalDialog} from 'react-bootstrap';
import CharacterInventory from './character-inventory';

class DraggableModalDialog extends React.Component {
  render() {
    return <Draggable handle=".character-inventory"><ModalDialog {...this.props} /></Draggable>
  }
}

export default class CharacterInventoryModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      characaterInventory: null,
      isLoading: true,
    }
  }

  componentDidMount() {
    axios.get('/api/character-inventory/' + this.props.characterId)
      .then((result) => {
        this.setState({
          characaterInventory: result.data.inventory.data,
          isLoading: false,
        });
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
          <Modal.Title><span className="character-inventory">Character Inventory</span></Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {this.state.isLoading ? 'Please wait ...' : <CharacterInventory inventory={this.state.characaterInventory} />}
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-primary" type="button" onClick={this.props.onClose}>
            Close
          </button>
        </Modal.Footer>
      </Modal>
    )
  }
}
