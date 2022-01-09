import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import ItemName from "../../../../marketboard/components/item-name";

export default class UseManyItemsModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      errorMessage: null,
      selectedItems: [],
    }
  }

  useSelected() {
    this.setState({
      errorMessage: null,
      loading: true,
    }, () => {
      if (this.state.selectedItems.length === 0) {
        return this.setState({
          errorMessage: 'Please select some items to use.',
          loading: false,
        });
      }

      if (this.state.selectedItems.length > 10) {
        return this.setState({
          errorMessage: 'You selected too many items. You may only have a max of ten boons applied at a time.',
          loading: false,
        });
      }

      axios.post('/api/character/'+this.props.characterId+'/inventory/use-many', {
        slot_ids: this.state.selectedItems,
      })
        .then((result) => {
          this.setState({
            loading: false,
          }, () => {
            this.props.setSuccessMessage(result.data.message);
            this.props.close();
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
              errorMessage: response.data.message
            });
          }

          if (response.data.hasOwnProperty('error')) {
            this.setState({
              errorMessage: response.data.error
            });
          }
        });
    });
  }

  setOptions() {
    return this.props.usableItems.filter((ui) => !ui.item.damages_kingdoms).map((ui) => <option value={ui.id} key={ui.id}>
      {ui.item.name}
    </option>)
  }

  setSelectedSet(event) {
    var options = event.target.options;
    var value   = [];

    for (var i = 0, l = options.length; i < l; i++) {
      if (options[i].selected) {
        value.push(parseInt(options[i].value) || 0);
      }
    }

    this.setState({
      selectedItems: value
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
          <Modal.Title>Use Multiple Items</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.selectedItems.length > 10 ?
              <div className="alert alert-danger mt-2 mb-3">
                <p>Nope. You can only use 10 items.</p>
              </div>
              : null
          }
          {
            this.state.errorMessage !== null ?
              <div className="alert alert-danger mt-2 mb-3">
                <p>{this.state.errorMessage}</p>
              </div>
              : null
          }
          <p>
            Below are the items you may use from your inventory. You may select a maximum of ten.
          </p>

          <p>
            You can use CTRL/CMD and SHIFT to manipulate your selections.
          </p>
          <p>
            <select className="form-control monster-select" id="monsters" name="monsters"
                    value={this.state.selectedSet}
                    onChange={this.setSelectedSet.bind(this)}
                    disabled={this.props.usableItems.length === 0}
                    multiple={true}
            >
              {this.setOptions()}
            </select>
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
          <Button variant="success" onClick={this.useSelected.bind(this)} disabled={this.state.selectedItems.length > 10 || this.props.usableItems.length === 0}>
            Use Items.
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
