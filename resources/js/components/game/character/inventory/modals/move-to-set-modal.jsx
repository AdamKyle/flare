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

  setOptions() {
    return this.props.sets.map((set) => {
     if (set.name !== null) {
       return <option value={set.id} key={set.id}>{set.name}</option>
     }

     return <option value={set.id} key={set.id}>Set {set.index}</option>
    });
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
            You may select a set below to which you wish to move this item to. You can only select sets that
            are not currently equipped.
          </p>
          <p>
            The set can be equipped if it complies with the rules of sets:
          </p>
          <p>
            <ul>
              <li>1 Weapon and 1 Shield or 2 Weapons or 1 Bow or 1 Hammer or 1 Stave</li>
              <li>1 Of each armour (Body, Leggings, Sleeves, Feet, Gloves and Helmet)</li>
              <li>2 Rings</li>
              <li>2 Spells (1 Healing, 1 Damage or 2 Healing or 2 Damage)</li>
              <li>2 Artifacts</li>
              <li>1 Unique of any type<sup>*</sup> (uniques are green items that can have one or two enchants).</li>
            </ul>
          </p>
          <p><sup>*</sup> IE, You can have 1 ring that has two regular enchants and 1 green ring that has 1 or 2 Unique Enchants. If you then add a helmet
          that also has unique enchants, the set will not be equippable.</p>
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
          <Button variant="secondary" onClick={this.props.close} disabled={this.state.loading}>
            Close
          </Button>
          <Button variant="success" onClick={this.destroyAll.bind(this)} disabled={this.state.loading}>
            Move to set.
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
