import React                      from 'react';
import BootstrapTable             from 'react-bootstrap-table-next';
import {Dropdown, Alert}          from 'react-bootstrap';
import CharacterEquipOptionsModal from './character-equip-options-modal';

import 'react-bootstrap-table-next/dist/react-bootstrap-table2.min.css'

export default class CharacterInventory extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      showEquipOptions: false,
      inventory:        this.props.inventory.items,
      equipment:        this.props.equipment,
      itemToEquip:      null,
      equippedItems:    null,
      message:          null,
    }
  }

  equip() {

    const foundItem = this.state.inventory.filter(i => i.id === parseInt(event.target.getAttribute('data-item-id')))[0];

    this.setState({
      showEquipOptions: true,
      itemToEquip:      foundItem,
      equippedItems:     this.state.equipment.filter(e => e.item.type === foundItem.type)
    });
  }

  closeEquiOptions() {
    this.setState({
      showEquipOptions: false,
      itemToEquip:      false,
      message:          null,
    });
  }

  closeEquiOptionsWithMessage(message) {
    this.setState({
      showEquipOptions: false,
      itemToEquip:      false,
      message:          message,
    });
  }

  render() {
    const inventory = this.state.inventory;

    // Set up the actions.
    equipAction     = this.equip.bind(this);

    const columns   = [{
      dataField: 'name',
      text: 'Item Name'
    }, {
      dataField: 'type',
      text: 'Item Type'
    }, {
      dataField: 'base_damage',
      text: 'Base Damage'
    }, {
      dataField: 'equipped',
      text: 'Is Equipped'
    }, {
      dataField: 'actions',
      text: 'Actions',
      formatter: actionsFormatter,
    }];

    return (
      <div>
        {this.state.message !== null
         ?
         <Alert variant="success" onClose={() => this.setState({message: null})} dismissible>
            {this.state.message}
         </Alert>
         : null
        }

        <div className="row">
          <div className="col-md-12">
            <BootstrapTable keyField='id' data={ inventory } columns={ columns } />
          </div>
        </div>

        <CharacterEquipOptionsModal
          show={this.state.showEquipOptions}
          onClose={this.closeEquiOptions.bind(this)}
          equippedItems={this.state.equippedItems}
          itemToEquip={this.state.itemToEquip}
          onEquip={this.closeEquiOptionsWithMessage.bind(this)}
        />
      </div>
    );
  }
}

let equipAction = null;

const actionsFormatter = (cell, row) => {
  if (row.hasOwnProperty('actions')) {
    return (
      <span>
        <Dropdown>
          <Dropdown.Toggle variant="primary" id="dropdown-basic">
            Actions
          </Dropdown.Toggle>

          <Dropdown.Menu>
            <Dropdown.Item data-item-id={row.id} onClick={equipAction}>Equip</Dropdown.Item>
            <Dropdown.Item href="#/action-3">Sell</Dropdown.Item>
            <Dropdown.Item href="#/action-2">Destroy</Dropdown.Item>
          </Dropdown.Menu>
        </Dropdown>
      </span>
    );
  }
}
