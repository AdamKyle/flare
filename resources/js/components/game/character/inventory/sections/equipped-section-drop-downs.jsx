import React from 'react';
import {Dropdown} from "react-bootstrap";
import DestroyModal from "../modals/destroy-modal";

export default class EquippedSectionDropDowns extends React.Component {

  constructor(props) {
    super(props);

    this.state = {


    }
  }

  unequipItem() {
    axios.post('/api/character/'+this.props.characterId+'/inventory/unequip', {
      inventory_set_equipped: this.props.hasSetEquipped,
      item_to_remove: this.props.findEquippedSlotId(this.props.item.id)
    }).then((result) => {
      this.props.setSuccessMessage(result.data.message)
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          return window.location.replace('/game');
        }

        if (response.data.hasOwnProperty('message')) {
          this.props.setErrorMessage(response.data.message)
        }

        if (response.data.hasOwnProperty('error')) {
          this.props.setErrorMessage(response.data.error)
        }
      }
    })
  }

  render() {
    return (
      <>
        <Dropdown disabled={this.props.loading}>
          <Dropdown.Toggle variant="primary" id="actions">
            Actions
          </Dropdown.Toggle>

          <Dropdown.Menu>
            <Dropdown.Item onClick={this.unequipItem.bind(this)}>Unequip</Dropdown.Item>
          </Dropdown.Menu>
        </Dropdown>

      </>
    );
  }
}