import React from 'react';
import {Dropdown} from "react-bootstrap";

export default class InventorySectionDropDowns extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      showDestroyModal: false,
      showSetModal: false,
    }
  }

  buildHref() {
    return '/game/character/inventory/compare/' + this.props.characterId +
      '?item_to_equip_type=' + this.props.item.type + '&slot_id=' +
      this.props.getSlotId(this.props.item.id)
  }

  manageDestroyModal() {
    this.setState({
      showDestroyModal: !this.state.showDestroyModal
    });
  }

  manageSetModal() {
    this.setState({
      showSetModal: !this.state.showSetModal
    });
  }

  render() {
    return (
      <Dropdown>
        <Dropdown.Toggle variant="primary" id="actions">
          Actions
        </Dropdown.Toggle>

        <Dropdown.Menu>
          <Dropdown.Item href={this.buildHref()}>Equip</Dropdown.Item>
          <Dropdown.Item onClick={this.manageDestroyModal.bind(this)}>Destroy</Dropdown.Item>
          <Dropdown.Item onClick={this.manageSetModal.bind(this)}>Assign to Set</Dropdown.Item>
        </Dropdown.Menu>
      </Dropdown>
    );
  }
}