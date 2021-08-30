import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import ItemDetails from "../item-details";
import MarketHistory from "../market-history";

export default class SellItemModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      list_for: 0,
      error: false,
      posting: false,
      error_message: null,
      loading: false,
    }
  }

  salePrice(event) {
    this.setState({
      list_for: event.target.value,
    });
  }


  list() {
    this.setState({
      error: false,
      error_message: null,
    }, () => {
      const listFor = parseInt(this.state.list_for) || 0;

      if (listFor < 0) {
        return this.setState({error: true, error_message: 'List price cannot be less then 0.'});
      }

      if (listFor === 0) {
        return this.setState({error: true, error_message: 'List for cannot be 0'});
      }

      this.setState({
        posting: true,
      }, () => {
        axios.post('/api/market-board/sell-item/' + this.props.characterId, {
          slot_id: this.props.modalData.slot_id,
          list_for: listFor,
        }).then((result) => {
          this.setState({
            posting: false,
          }, () => {
            this.props.closeModal(true)
          });
        }).catch((err) => {
          if (err.hasOwnProperty('response')) {
            const response = err.response;

            if (response.status === 401) {
              return lodation.reload;
            }

            if (response.status === 429) {
              return window.location = '/game';
            }

            this.setState({
              error: true,
              errorMessage: response.data.message,
              posting: false,
            });
          }
        })
      });
    });
  }

  render() {
    const suggestedPrice = this.props.modalData.cost;
    console.log(this.props.modalData);
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
          <Modal.Title>{this.props.modalData.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          { this.state.error ?
            <div className="alert alert-danger mb-2 mt-2">
              {this.state.error_message}
            </div> : null
          }
          <div className="alert alert-info">
            Chart shows the sale history of this item (with exact affixes attached) over time so you can gauge the average buying price. The number input for you, is a place holder
            only, of the cost of the item plus cost of attached affixes. Use this as a guide only.
          </div>
          <div className="form-group">
            <label htmlFor="listPirce">List For (suggested price: {suggestedPrice > 0 ? suggestedPrice.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' Gold' : 'anything'})</label>
            <input type="number" min={0} className="form-control" id="listPirce" onChange={this.salePrice.bind(this)} />
            <small id="emailHelp" className="form-text text-muted">There is a 5% sales tax for listing items.</small>
          </div>
          <MarketHistory type={this.props.modalData.type}/>
          <ItemDetails item={this.props.modalData} />
          {
            this.state.posting ?
              <div className="progress mb-2 mt-2" style={{position: 'relative', height: '5px'}}>
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
            onClick={this.list.bind(this)}
          >
            List
          </Button>
        </Modal.Footer>
      </Modal>
    );
  }
}
