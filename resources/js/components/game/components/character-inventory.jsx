import React                        from 'react';
import BootstrapTable               from 'react-bootstrap-table-next';
import {Dropdown, Alert}            from 'react-bootstrap';
import CharacterEquipOptionsModal   from './character-equip-options-modal';
import CharacterDestroyWarningModal from './character-destroy-warning-modal';

import 'react-bootstrap-table-next/dist/react-bootstrap-table2.min.css'

export default class CharacterInventory extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      showEquipOptions: false,
      showWarning:      false,
      inventory:        this.props.inventory.items,
      equipment:        this.props.equipment,
      itemToEquip:      null,
      itemToDestroy:    null,
      equippedItems:    null,
      message:          null,
      error:            null,
    }
  }

  componentDidUpdate(prevProps, prevState) {
    if (prevProps.equipment !== this.props.equipment) {
      this.setState({
        equipment: this.props.equipment,
        inventory: this.props.inventory.items,
      });
    }
  }

  equip(event) {
    const foundItem = this.state.inventory.filter(i => i.id === parseInt(event.target.getAttribute('data-item-id')))[0];

    this.setState({
      showEquipOptions: true,
      itemToEquip:      foundItem,
      equippedItems:    this.state.equipment.filter(e => e.item.type === foundItem.type)
    });
  }

  destroyItem(event) {
    const foundItem = this.state.inventory.filter(i => i.id === parseInt(event.target.getAttribute('data-item-id')))[0];

    if (foundItem.type === 'quest') {
      return this.setState({
        'error': 'Cannot detroy quest items.',
      });
    }

    this.setState({
      error: null,
      message: null,
      showWarning: true,
      itemToDestroy: foundItem,
    });
  }

  closeDestroyWarning() {
    this.setState({
      error: null,
      message: null,
      showWarning: false,
      itemToDestroy: null,
    });
  }

  unEquip(event) {
    const foundItem = this.state.equipment.filter(e => e.id === parseInt(event.target.getAttribute('data-equipment-id')))[0];

    this.setState({
      message: null,
      error: null,
    });

    if (typeof foundItem === 'undefined') {
      return this.setState({
        error: 'Could not unequip item.',
      });
    }

    axios.delete('/api/unequip-item/' + this.props.characterId, {
      data: {
        equipment_id: foundItem.id
      }
    }).then((result) => {
      this.setState({
        message: result.data.message
      });
    }).catch((error) => {
      const result = error.response;

      this.setState({
        error: result.data.message
      });
    });
  }

  closeEquiOptions() {
    this.setState({
      showEquipOptions: false,
      itemToEquip:      false,
      message:          null,
      error:            null,
    });
  }

  closeEquiOptionsWithMessage(message) {
    this.setState({
      showEquipOptions: false,
      itemToEquip:      false,
      message:          message,
      error:            null,
    });
  }

  closeDestroyWarningWithMesage(message) {
    this.setState({
      showWarning: false,
      message: message,
      error: null,
      itemToDestroy: null,
    });
  }

  fetchEquippedIds() {
    return this.state.equipment.map((item) => {
      return item.item.id;
    });
  }

  render() {
    const equippedIds = this.fetchEquippedIds();
    const inventory   = this.state.inventory.filter(i => !equippedIds.includes(i.id));

    // Set up the actions.
    equipAction     = this.equip.bind(this);
    unEquipAction   = this.unEquip.bind(this);
    destroyAction   = this.destroyItem.bind(this);

    const columns   = [{
      dataField: 'name',
      text: 'Item Name',
    }, {
      dataField: 'type',
      text: 'Item Type'
    }, {
      dataField: 'base_damage',
      text: 'Base Damage'
    }, {
      dataField: 'max_damage',
      text: 'Max Damage'
    }, {
      dataField: 'actions',
      text: 'Actions',
      formatter: actionsFormatter,
    }];

    const equipmentColumns   = [{
      dataField: 'item.name',
      text: 'Item Name',
    }, {
      dataField: 'item.type',
      text: 'Item Type'
    }, {
      dataField: 'item.base_damage',
      text: 'Base Damage'
    }, {
      dataField: 'item.max_damage',
      text: 'Max Damage'
    }, {
      dataField: 'type',
      text: 'Equipped Position'
    }, {
      dataField: 'actions',
      text: 'Actions',
      formatter: equipmentActionsFormatter,
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

        {this.state.error !== null
         ?
         <Alert variant="danger" onClose={() => this.setState({error: null})} dismissible>
            {this.state.error}
         </Alert>
         : null
        }

        <div className="row">
          <div className="col-md-12">
            <BootstrapTable keyField='slot_id' data={ inventory } columns={ columns } />
          </div>
        </div>
        <hr />
        <h5>Equipped</h5>
        <div className="row">
          <div className="col-md-12">
            <BootstrapTable keyField='id' data={ this.state.equipment } columns={ equipmentColumns } />
          </div>
        </div>

        <CharacterEquipOptionsModal
          show={this.state.showEquipOptions}
          onClose={this.closeEquiOptions.bind(this)}
          equippedItems={this.state.equippedItems}
          itemToEquip={this.state.itemToEquip}
          onEquip={this.closeEquiOptionsWithMessage.bind(this)}
          characterId={this.props.characterId}
        />

        <CharacterDestroyWarningModal
          show={this.state.showWarning}
          onClose={this.closeDestroyWarning.bind(this)}
          itemToDestroy={this.state.itemToDestroy}
          onDestroyed={this.closeDestroyWarningWithMesage.bind(this)}
          characterId={this.props.characterId}
        />
      </div>
    );
  }
}

let equipAction   = null;
let unEquipAction = null;
let destroyAction = null;

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
            <Dropdown.Item data-item-id={row.id} onClick={destroyAction} className="text-danger">
              Destroy
            </Dropdown.Item>
          </Dropdown.Menu>
        </Dropdown>
      </span>
    );
  }
}

const equipmentActionsFormatter = (cell, row) => {
  if (row.hasOwnProperty('actions')) {
    return (
      <span>
        <Dropdown>
          <Dropdown.Toggle variant="primary" id="dropdown-basic">
            Actions
          </Dropdown.Toggle>

          <Dropdown.Menu>
            <Dropdown.Item data-equipment-id={row.id} onClick={unEquipAction}>Unequip</Dropdown.Item>
          </Dropdown.Menu>
        </Dropdown>
      </span>
    );
  }
}
