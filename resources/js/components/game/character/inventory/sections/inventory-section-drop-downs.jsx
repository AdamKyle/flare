import React from 'react';
import {Dropdown} from "react-bootstrap";
import DestroyModal from "../modals/destroy-modal";

export default class InventorySectionDropDowns extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      showDestroyModal: false,
      showSetModal: false,
    }
  }

  buildHref() {
    let type = this.props.item.type;

    if (type === 'bow') {
      type = 'weapon';
    }

    return '/game/character/inventory/compare/' + this.props.characterId +
      '?item_to_equip_type=' + type + '&slot_id=' +
      this.props.getSlotId(this.props.item.id)
  }

  canDisenchant() {
    return this.props.item.item_suffix_id !== null || this.props.item.item_prefix_id !== null
  }

  render() {
    return (
      <>
        <Dropdown>
          <Dropdown.Toggle variant="primary" id="actions">
            Actions
          </Dropdown.Toggle>

          <Dropdown.Menu>
            <Dropdown.Item href={this.buildHref()} target="_blank">Equip</Dropdown.Item>
            <Dropdown.Item onClick={() => this.props.manageDestroyModal(this.props.item)}>Destroy</Dropdown.Item>
            {
              this.canDisenchant() ?
                <Dropdown.Item onClick={() => this.props.manageDisenchantItemModal(this.props.item)}>Disenchant</Dropdown.Item>
              : null
            }
            <Dropdown.Item onClick={() => this.props.manageMoveItemToSetModal(this.props.item)}>Assign to Set</Dropdown.Item>
          </Dropdown.Menu>
        </Dropdown>

      </>
    );
  }
}
