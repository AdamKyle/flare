import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class KingdomSettlementModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      kingdom_name: '',
      errorMessage: null,
    }
  }

  updateErrorMessage(message) {
    this.setState({
      errorMessage: message,
    });
  }

  handleNameChange(event) {
    this.setState({
      kingdom_name: event.target.value
    });
  }

  settle() {
    const kingdomName = this.state.kingdom_name.trim();

    this.setState({
      errorMessage: null
    });

    if (kingdomName === '') {
      this.setState({
        errorMessage: 'Kingdom needs a name.'
      });
    }

    if (kingdomName.length < 5) {
      this.setState({
        errorMessage: 'Kingdom name must be minum of 5 characters.'
      })
    }

    if (kingdomName.length > 30) {
      this.setState({
        errorMessage: 'Kingdom name can only be maximum of 30 characters.'
      });
    }

    const params = {
      name: this.state.kingdom_name,
      x_position: this.props.x,
      y_position: this.props.y,
    }

    axios.post('/api/kingdoms/' + this.props.characterId + '/settle', params).then((result) => {
      this.props.close();
      this.props.openKingdomManagement();
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        this.setState({
          errorMessage: error.response.data.errors.name[0],
        });
      } else {
        console.error(error);
      }
    });
  }

  render() {
    return (
      <Modal show={this.props.show} onHide={this.props.close} backdrop="static" keyboard={false}>
        <Modal.Header closeButton>
          <Modal.Title>Settle Kingdom</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <>
            {this.state.errorMessage !== null ?
              <div className="alert alert-danger mt-2 mb-2">{this.state.errorMessage}</div> : null}
            <form>
              <div className="form-row">
                <div className="form-group col-md-12">
                  <label htmlFor="kingdom-name">Kingdom Name</label>
                  <input type="text" className="form-control" id="kingdom-name"
                         value={this.state.kingdom_name} onChange={this.handleNameChange.bind(this)}/>
                </div>
                <div className="alert alert-info mt-2">
                  <p>Kingdom names may be up to 30 characters long and contain spaces. Kingdom colors will be picked for you based on the
                    plane of existence you are currently on. You may change your kingdom name via the kingdoms screen or individually by visiting the kingdom.</p>
                </div>
              </div>
            </form>
          </>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="danger" onClick={this.props.close}>
            Cancel
          </Button>
          <Button variant="success" onClick={this.settle.bind(this)}>
            Settle
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
