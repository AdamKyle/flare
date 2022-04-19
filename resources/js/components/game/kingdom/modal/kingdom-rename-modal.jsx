import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class KingdomRenameModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      kingdom_name: '',
      errorMessage: null,
    }
  }

  componentDidMount() {
    this.setState({
      kingdom_name: this.props.kingdomName,
    });
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

  rename() {
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
        errorMessage: 'Kingdom name must be a minimum of 5 characters.'
      })
    }

    if (kingdomName.length > 30) {
      this.setState({
        errorMessage: 'Kingdom name can only be a maximum of 30 characters.'
      });
    }

    const params = {
      name: this.state.kingdom_name,
    }

    axios.post('/api/kingdom/'+this.props.kingdomId+'/rename', params).then((result) => {
      this.props.close();
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          this.props.openTimeOutModal();
        }

        this.setState({
          errorMessage: response.data.errors.name[0],
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
          <Modal.Title>Rename Kingdom</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <>
            {this.state.errorMessage !== null ?
              <div className="alert alert-danger mt-2 mb-2">{this.state.errorMessage}</div> : null}
            <form>
              <div className="form-row">
                <div className="form-group col-md-12">
                  <label htmlFor="kingdom-name">New Kingdom Name</label>
                  <input type="text" className="form-control" id="kingdom-name"
                         value={this.state.kingdom_name} onChange={this.handleNameChange.bind(this)}/>
                </div>
              </div>
            </form>
          </>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="danger" onClick={this.props.close}>
            Cancel
          </Button>
          <Button variant="success" onClick={this.rename.bind(this)}>
            Rename
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
