import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class MoveToSetModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      showError: false,
      errorMessage: null,
      selectedSet: "",
    }
  }

  destroyAll() {
    this.setState({
      showError: false,
      errorMessage: null,
      loading: true,
    }, () => {
      if (this.state.selectedSet === "") {
        return this.setState({
          showError: true,
          errorMessage: 'Please select a set.',
          loading: false,
        });
      }

      axios.post('/api/character/'+this.props.characterId+'/inventory/move-to-set', {
        slot_id: this.props.getSlotId(this.props.item.id),
        move_to_set:  this.state.selectedSet,
      })
        .then((result) => {
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

  setOptions() {
    return this.props.sets.map((set) => <option value={set} key={set}>Set {set}</option>)
  }

  setSelectedSet(event) {
    this.setState({
      selectedSet: parseInt(event.target.value) || ""
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
          <Modal.Title>Move to set</Modal.Title>
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
            You may select below a set which you wish to move this item to. You can only select sets that
            are not currently equipped.
          </p>
          <p>
            The set can be equipped if it complies with the rules of sets:
          </p>
          <p>
            <ul>
              <li>1 Weapon, 1 Shield or 2 Weapons or 1 Bow</li>
              <li>1 Of each armour (Body, Leggings, Sleeves, Feet, Gloves and Helmet)</li>
              <li>2 Rings</li>
              <li>2 Spells (1 Healing, 1 Damage or 2 Healing or 2 Damage)</li>
              <li>2 Artifacts</li>
            </ul>
          </p>
          <p>
            <select className="form-control monster-select" id="monsters" name="monsters"
                    value={this.state.selectedSet}
                    onChange={this.setSelectedSet.bind(this)}
            >
              <option value="" key="-1">Please select a set</option>
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
          <Button variant="success" onClick={this.destroyAll.bind(this)}>
            Move to set.
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
