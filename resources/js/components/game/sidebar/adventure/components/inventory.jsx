import React from 'react';
import {Alert} from 'react-bootstrap'

export default class Inventory extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      inventoryItemId: 0,
      message: null,
      errorMessage: null,
    }
  }

  renderInventoryList() {
    return this.props.inventory.map((slot) => {
      return <option key={"slot-" + slot.id} value={slot.item.id}>{slot.item.name}</option>
    });
  }

  selectInventoryItem(event) {
    this.setState({
      inventoryItemId: event.target.value !== 0 ? event.target.value : 0,
    });
  }

  sellItem() {
    this.setState({
      errorMessage: null,
      message: null,
    });

    axios.post('/api/shop/sell/' + this.props.characterId, {
      item_id: this.state.inventoryItemId,
    }).then((result) => {
      this.setState({
        message: result.data.message,
        inventoryItemId: 0,
      });
    }).catch((error) => {
      const response = error.response;

      this.setState({
        errorMessage: response.data.message,
        inventoryItemId: 0,
      });
    });
  }

  render() {
    return(
      <div className="form-row">
        {this.state.errorMessage !== null
         ?
         <Alert variant="danger" onClose={() => this.setState({errorMessage: null})} dismissible>
           {this.state.errorMessage}
         </Alert>
         : null
        }

        {this.state.message !== null
         ?
         <Alert variant="success" onClose={() => this.setState({message: null})} dismissible>
           {this.state.message}
         </Alert>
         : null
        }

        <div className="form-group col-md-8">
          <select value={this.state.inventoryItemId} className="form-control" id="weapons" onChange={this.selectInventoryItem.bind(this)}>
            <option value={0}>---Inventry---</option>
            {this.renderInventoryList()}
          </select>
        </div>
        <div className="form-group col-md-4">
          <button type="submit" className="btn btn-success btn-sm mb-2 ml-2" disabled={this.state.inventoryItemId === 0} onClick={this.sellItem.bind(this)}>Sell</button>
        </div>
      </div>
    );
  }

}
