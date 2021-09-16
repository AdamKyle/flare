import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class DestroyModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      showError: false,
      errorMessage: null,
    }
  }

  destroyItem() {
    this.setState({
      showError: false,
      errorMessage: null,
      loading: true,
    }, () => {
      axios.post('/api/character/'+this.props.characterId+'/inventory/destroy', {
        slot_id: this.props.getSlotId(this.props.item.id),
      }).then((result) => {
        this.setState({
          loading: false,
        }, () => {
          this.props.close(null, result.data.message);
        });
      }).catch((error) => {
        this.setState({loading: false});
        const response = error.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          return window.location.replace('/game');
        }

        if (response.data.hasOwnProperty('message')) {
          this.setState({
            showError: true,
            errorMessage: response.data.message
          });
        }

        if (response.data.hasOwnProperty('error')) {
          this.setState({
            showError: true,
            errorMessage: response.data.error
          });
        }
      });
    });
  }

  render() {
    return (
      <Modal
        show={this.props.open}
        onHide={this.props.close}
        backdrop="static"
      >
        <Modal.Header closeButton>
          <Modal.Title>Destroy: {this.props.item.affix_name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.showError ?
              <div className="alert alert-danger mt-2 mb-3">
                <p>{this.state.errorMessage}</p>
              </div>
            : null
          }
          <p>
            Destroying this item will get you no gold dust, even if it is enchanted.
            The item will be lost, this is irreversible. Are you positive you want to do this?
          </p>
          {
            this.state.loading ?
              <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
              : null
          }
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={this.props.close}>
            Close
          </Button>
          <Button variant="success" onClick={this.destroyItem.bind(this)}>
            I Understand. Destroy.
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
