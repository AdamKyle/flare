import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class DisenchantModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      showError: false,
      errorMessage: null,
    }
  }

  disenchantAll() {
    this.setState({
      showError: false,
      errorMessage: null,
      loading: true,
    }, () => {
      axios.post('/api/character/'+this.props.characterId+'/inventory/disenchant-all')
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

          if (response.hasOwnProperty('message')) {
            this.setState({
              showError: true,
              errorMessage: response.message
            });
          }

          if (response.hasOwnProperty('error')) {
            this.setState({
              showError: true,
              errorMessage: response.message
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
          <Modal.Title>Disenchant All</Modal.Title>
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
            This will fire off a job in the background allowing you to see the results from
            the chat window. Once we have finished, we will, through the chat window, show you
            the total gold dust you gained.
          </p>

          <p>
            You will be free to leave the character page and continue on as normal. Your inventory will automatically
            update in real time as each item is disenchanted.
          </p>

          <p>
            Sets and equipped items will not be touched.
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
          <Button variant="success" onClick={this.disenchantAll.bind(this)}>
            I Understand. Disenchant All.
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
