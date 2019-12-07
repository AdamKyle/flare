import React from 'react';
import Draggable from 'react-draggable';
import {Modal, ModalDialog} from 'react-bootstrap';
import CharacterDetroyWarning from './character-destroy-warning';

class DraggableModalDialog extends React.Component {
  render() {
    return <Draggable handle=".character-detroy-warning"><ModalDialog {...this.props} /></Draggable>
  }
}

export default class CharacterDestroyWarningModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      error: null,
    };
  }

  destroy() {
    this.setState({
      error: null,
    });

    axios.delete('/api/destroy-item/' + this.props.characterId, {
      data: {
        item_id: this.props.itemToDestroy.id,
      }
    }).then((result) => {
      this.props.onDestroyed(result.data.message);
    }).catch((error) => {
      this.setState({
        error: error.response.message,
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
        size="md"
        aria-labelledby="contained-modal-title-vcenter"
        centered
      >
        <Modal.Header closeButton>
          <Modal.Title><span className="character-detroy-warning">Are you Sure?</span></Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {this.state.error !== null
           ?
           <div className="row mb-2">
              <div className="col-md-12">
                <div className="alert alert-danger">{this.state.error}</div>
              </div>
           </div>
           : null
          }
          <CharacterDetroyWarning itemToDestroy={this.props.itemToDestroy} characterId={this.props.characterId} />
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-danger" type="button" onClick={this.destroy.bind(this)}>
            Destroy
          </button>
          <button className="btn btn-primary" type="button" onClick={this.props.onClose}>
            Close
          </button>
        </Modal.Footer>
      </Modal>
    );
  }
}
