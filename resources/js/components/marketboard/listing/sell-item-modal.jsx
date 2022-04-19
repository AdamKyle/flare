import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import {debounce} from "lodash";
import ItemDetails from "../item-details";
import MarketHistory from "../market-history";
import UsableItemDetails from "../usable-item-details";
import ItemName from "../components/item-name";
import AlertInfo from "../../game/components/base/alert-info";
import AlertError from "../../game/components/base/alert-error";

export default class SellItemModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      list_for: '',
      posting: false,
      error_message: null,
      loading: false,
    }
  }

  componentDidMount() {
    const minCost = parseInt(this.props.modalData.min_cost) || 0;

    if (minCost !== 0) {
      return this.setState({
        list_for: minCost,
      });
    }
  }

  debouncedEvent = debounce((price) => {
    const minCost = parseInt(this.props.modalData.min_cost) || 0;

    if (minCost !== 0) {
      if (price < minCost) {
        return this.setState({
          error_message: 'No. The minimum price allowed is: ' + minCost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","),
          list_for: price,
        });
      }
    }
  }, 1000)

  salePrice(event) {
    const value = parseInt(event.target.value) || 0

    this.setState({
      error_message: null,
      list_for: value
    });

    this.debouncedEvent(value);
  }


  list() {
    this.setState({
      error: false,
      error_message: null,
    }, () => {
      const listFor = parseInt(this.state.list_for) || 0;

      if (listFor < 0) {
        return this.setState({error: true, error_message: 'List price cannot be less than 0.'});
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
              return location.reload;
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

  buildListForLabel() {
    let label = 'List for ';

    if (this.props.modalData.min_cost) {
      label += '(Minimum selling price allowed: '+this.props.modalData.min_cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")+' Gold)';
    } else {
      label += '(Suggested selling price: '+this.props.modalData.cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")+' Gold)';
    }

    return label
  }

  render() {

    const suggestedPrice = this.props.modalData.cost;

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
            <ItemName item={this.props.modalData} />
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          { this.state.error_message !== null ?
              <AlertError icon={"fas fa-exclamation"} title={'Oops!'}>
                <p>{this.state.error_message}</p>
              </AlertError>
            : null
          }
          <AlertInfo icon={"fas fa-question-circle"} title={"Help"}>
            <p>
              Chart shows the sale history of this item (with exact affixes attached) over time so you can gauge the average buying price. The number input for you, is a place holder
              only, of the cost of the item plus cost of attached affixes. Use this as a guide only.
            </p>
            <p>
              If the value in the input is supplied for you, the item cannot fall below that minimum price. This will apply to special items like <a href="/information/random-enchantments">uniques</a>.
              If there is no value (or 0) then you are free to enter any price you see fit.
            </p>
            <p>
              If the item is a <a href="/information/holy-items">Holy item</a> there is additional cost added of 1 billion per stack on the item. This additional cost
              will be applied to uniques that also have holy stacks.
            </p>
          </AlertInfo>
          <div className="form-group">
            <label htmlFor="listPrice">
              {this.buildListForLabel()}
            </label>
            <input type="number"
                   min={this.props.modalData.min_cost}
                   className="form-control"
                   id="listPrice"
                   onChange={this.salePrice.bind(this)}
                   value={this.state.list_for}
            />
            <small id="listPriceHelp" className="form-text text-muted">There is a 5% sales tax when this item sells.</small>
          </div>
          <MarketHistory type={this.props.modalData.type}/>
          {
            this.props.modalData.usable ?
              <UsableItemDetails item={this.props.modalData}/>
            :
              <ItemDetails item={this.props.modalData}/>
          }
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
            disabled={this.state.error_message !== null}
          >
            List
          </Button>
        </Modal.Footer>
      </Modal>
    );
  }
}
