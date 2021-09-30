import React from 'react';
import {Modal, Button, Alert} from 'react-bootstrap';
import ItemDetails from './item-details';
import UsableItemDetails from "./usable-item-details";

export default class PurchaseModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      item: null,
      loading: true,
      posting: false,
      errorMessage: null,
    }
  }

  componentDidMount() {
    axios.get('/api/market-board/' + this.props.modalData.item_id + '/listing-details')
      .then((result) => {
        this.setState({
          item: result.data,
          loading: false,
        })
      }).catch((error) => {
        if (error.hasOwnProperty('response')) {
          const response = error.response;

          if (response.status === 401) {
            return location.reload();
          }

          if (response.status === 429) {
            return window.location = '/game';
          }
        }
      });
  }

  hasEnoughGold() {
    return (this.props.characterGold - (this.props.modalData.listed_price * 1.05)) > 0;
  }

  belongsToCharacter() {
    return parseInt(this.props.characterId) === this.props.modalData.character_id;
  }

  removeError() {
    this.setState({
      errorMessage: null,
    })
  }

  purchase() {
    this.setState({
      purchasing: true,
      errorMessage: null,
    }, () => {
      axios.post('/api/market-board/purchase/' + this.props.characterId, {
        market_board_id: this.props.modalData.id
      }).then((result) => {
        this.props.updateMessage('You purchased the ' + this.props.modalData.name + ' for: ' + (this.props.modalData.listed_price * 1.05) + ' Gold.', 'success');
        this.props.closeModal();
      }).catch((err) => {
        this.setState({purchasing: false});
        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 401) {
            location.reload();
          }

          if (response.status === 429) {
            return window.location = '/game';
          }

          if (response.data.hasOwnProperty('message')) {
            this.setState({
              errorMessage: response.data.message
            });
          }
        }
      });
    });
  }

  getCostPercentage() {
    if (this.state.item.usable) {
      return 0.0;
    }

    return (this.props.modalData.listed_price / this.state.item.cost);
  }

  render() {
    let percentage = 0.0;
    let text = '';

    if (!this.state.loading) {
      percentage = this.getCostPercentage();

    }

    if (percentage > 1.0) {
      text       = 'above';
      percentage = percentage.toFixed(2) * 100;
    } else if (percentage <= 0.0) {
      text = 'Alchemy Item (unknown base cost)';
      percentage = 0;
    } else {
      percentage = 100 - 100 * percentage.toFixed(2);

      if (percentage === 0.0) {
        text = 'far below'
      } else {
        text = 'below'
      }
    }

    return (
      <Modal
        show={this.props.showModal}
        onHide={this.props.closeModal}
        backdrop="static"
        keyboard={false}
        dialogClassName="large-modal "
        size="lg"
      >
        <Modal.Header closeButton>
          <Modal.Title>
            {this.props.modalData.name}
            {this.state.loading ?
              <span className={'ml-2'} style={{fontSize: '16px', position: 'realitive', top: '-10px'}}>calculating ...</span> :
              <span className={percentage > 100.00 ? 'text-danger' : 'text-success' + " ml-2"} style={{fontSize: '16px', position: 'relative', top: '-2px', left: '5px'}}>
                {
                  percentage === 0 ?
                    <>{text}</>
                  :
                    <>{percentage}% {text} base cost</>
                }
              </span>
            }

          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            !this.hasEnoughGold() && !this.belongsToCharacter() ?
              <div className="alert alert-danger mb-2 mt-2">You do not have enough gold to buy this.</div> : null
          }

          {
            this.belongsToCharacter() ?
              <div className="alert alert-danger mb-2 mt-2">You cannot purchase your own item.
                If you would like to delist this item, head over to your My listings section under the market to delist.</div> : null
          }
          {
            this.state.errorMessage !== null ?
              <Alert variant="danger" onClose={this.removeError.bind(this)} dismissible>
                {this.state.errorMessage}
              </Alert>
            : null
          }
          <p>Is this the item you would like to purchase? It
            will <strong>cost</strong>: {(this.props.modalData.listed_price * 1.05).toFixed(0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")} Gold (incl. 5% tax)</p>
          {this.state.loading ? 'Loading please wait ...' : <>
            <p><strong> Base cost</strong>: {this.state.item.cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")} Gold</p>

            <p>Base cost is the item cost + cost of attached affixes. This is handy to know if the item is more expensive then making it your self, or significantly cheaper. Most items will be listed for higher,
              due to the inherit risk in crafting and enchanting.</p>
          </> }
          { this.state.loading ?
            'Loading please wait ...' :
              this.state.item.usable ?
                <UsableItemDetails item={this.state.item} /> :
                  <ItemDetails item={this.state.item}/>
          }
          {
            this.state.purchasing ?
              <div className="progress loading-progress mt-3" style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
              : null
          }
        </Modal.Body>
        <Modal.Footer>
          <Button variant="danger" onClick={this.props.closeModal}>
            Close
          </Button>
          <Button
            variant="primary"
            onClick={this.purchase.bind(this)}
            disabled={!this.hasEnoughGold() || this.belongsToCharacter()}
          >
            Purchase
          </Button>
        </Modal.Footer>
      </Modal>
    );
  }
}
