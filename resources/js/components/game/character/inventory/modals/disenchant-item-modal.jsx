import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import ItemName from "../../../../marketboard/components/item-name";

export default class DisenchantItemModal extends React.Component {

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
      axios.post('/api/disenchant/' + this.props.item.id)
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
          <Modal.Title>Disenchant <ItemName item={this.props.item} /></Modal.Title>
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
            This item will be disenchanted and cannot be undone. Are you sure you want to do this?
          </p>

          <p>
            Disenchanting gives you skill XP towards disenchanting and half towards enchanting. Gold dust is used in quests,
            special types of crafting and to purchase some powerful items later in the game.
            See <a href={"/information/currencies"}>Currencies</a> for more info.
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
            I Understand. Disenchant Item.
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
