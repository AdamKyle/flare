import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class DestroyAllModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      showError: false,
      errorMessage: null,
    }
  }

  destroyAll() {
    this.setState({
      showError: false,
      errorMessage: null,
      loading: true,
    }, () => {
      axios.post('/api/character/'+this.props.characterId+'/inventory/destroy-all')
        .then((result) => {
          this.setState({
            loading: false,
          }, () => {
            this.props.close(result.data.message);
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
          <Modal.Title>Destroy All</Modal.Title>
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
            This will destroy <strong>ALL</strong> items in your inventory that are not currently equipped or
            in a set. No gold dust will be given for this action. Are you sure?
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
          <Button variant="success" onClick={this.destroyAll.bind(this)}>
            I Understand. Destroy All.
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
