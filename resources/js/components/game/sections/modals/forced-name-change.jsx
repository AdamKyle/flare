import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class ForcedNameChange extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      newName: '',
      errorMessage: null,
    }
  }

  changeName() {
    if (this.state.newName === '') {
      this.setState({
        errorMessage: 'Name is required.'
      });

      return;
    }

    this.setState({
      errorMessage: null,
    });

    axios.post('/api/character-sheet/' + this.props.characterId + '/name-change', {
      name: this.state.newName
    }).then((result) => {
      location.reload();
    }).catch((error) => {
      this.setState({
        errorMessage: error.response.data.errors
      });
    });
  }

  handleNewName(event) {
    this.setState({
      newName: event.target.value
    });
  }

  fetchMessage() {
    if (typeof this.state.errorMessage === 'object') {
      let message = '';

      for (const key in this.state.errorMessage) {
        message = this.state.errorMessage[key][0];
      }

      return message;
    }

    return this.state.errorMessage;
  }

  render() {

    return (
      <>
        <Modal show={true} keyboard={false} backdrop="static">
          <Modal.Header>
            <Modal.Title>Forced Name Change</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {this.state.errorMessage !== null
              ? <div className="alert alert-danger">{this.fetchMessage()}</div>
              : null
            }
            <div className="alert alert-info" role="alert">
              <h4 className="alert-heading">You are being forced to change your name.</h4>
              <p>
                Your name violates the <a href="/information/rules">rules</a> pretaining to character creation.
              </p>
              <p>
                Any attempts to circumvent changing your character name is a bannable offense. Once your name has been
                changed
                this page will refresh to take affect.
              </p>
            </div>
            <div className="form-group row">
              <label className="col-md-4 col-form-label text-md-right">New Name</label>
              <div className="col-md-6">
                <input type="text" value={this.state.newName} onChange={this.handleNewName.bind(this)}/>
              </div>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="primary" onClick={this.changeName.bind(this)}>
              Change Name
            </Button>
          </Modal.Footer>
        </Modal>
      </>
    );
  }
}
