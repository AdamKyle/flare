import React                        from 'react';
import BootstrapTable               from 'react-bootstrap-table-next';
import {Dropdown,
        DropdownButton,
        Alert,
        Popover,
        OverlayTrigger,
        Accordion,
        Card}                       from 'react-bootstrap';
import CharacterEquipOptionsModal   from './character-equip-options-modal';
import CharacterDestroyWarningModal from './character-destroy-warning-modal';
import ItemInfo                     from './item-info';

import 'react-bootstrap-table-next/dist/react-bootstrap-table2.min.css'

export default class CharacterInventory extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      showEquipOptions:    false,
      showWarning:         false,
      showItemDescription: false,
      inventory:           this.props.inventory.items,
      equipment:           this.props.equipment,
      questItems:          this.props.questItems,
      itemToEquip:         null,
      itemToDestroy:       null,
      equippedItems:       null,
      message:             null,
      error:               null,
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
      formatter: nameFormatter,
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
      formatter: equipmentNameFormatter,
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

    const questItems   = [{
      dataField: 'item.name',
      text: 'Item Name',
      formatter: questItemNameFormatter,
    }, {
      dataField: 'item.type',
      text: 'Item Type'
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

        <div className="row mb-2">
          <div className="col-md-12">
            <Accordion defaultActiveKey="0">
              <Card>
                <Accordion.Toggle as={Card.Header} eventKey="0">
                  Inventory
                </Accordion.Toggle>
                <Accordion.Collapse eventKey="0">
                  <Card.Body>
                    <BootstrapTable keyField='slot_id' data={ inventory } columns={ columns } />
                  </Card.Body>
                </Accordion.Collapse>
              </Card>
            </Accordion>
          </div>
        </div>
        <div className="row mb-2">
          <div className="col-md-12">
            <Accordion defaultActiveKey="0">
              <Card>
                <Accordion.Toggle as={Card.Header} eventKey="0">
                  Equipment
                </Accordion.Toggle>
                <Accordion.Collapse eventKey="0">
                  <Card.Body>
                    <BootstrapTable keyField='id' data={ this.state.equipment } columns={ equipmentColumns } />
                  </Card.Body>
                </Accordion.Collapse>
              </Card>
            </Accordion>
          </div>
        </div>
        <div className="row mb-2">
          <div className="col-md-12">
            <Accordion>
              <Card>
                <Accordion.Toggle as={Card.Header} eventKey="0">
                  Quest Items
                </Accordion.Toggle>
                <Accordion.Collapse eventKey="0">
                  <Card.Body>
                    <BootstrapTable keyField='id' data={ this.state.questItems } columns={ questItems } />
                  </Card.Body>
                </Accordion.Collapse>
              </Card>
            </Accordion>
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

const nameFormatter = (cell, row) => {

  let className = 'regular-item';

  if (row.artifact_property !== null) {
    className = 'artifact-item';
  }

  if (row.item_affixes.length > 0) {
    className = 'enchanted-item';
  }

  if (row.item_affixes.length > 0 && row.artifact_property !== null) {
    className = 'magical-item';
  }

  const popover = (
    <Popover id="inventory-item">
      <ItemInfo item={row} />
    </Popover>
  );

  return (
    <OverlayTrigger placement="right" overlay={popover}>
      <a href="#" className={className}>{row.name}</a>
    </OverlayTrigger>
  );
}

const equipmentNameFormatter = (cell, row) => {

  let className = 'regular-item';

  if (row.item.artifact_property !== null) {
    className = 'artifact-item';
  }

  if (row.item.item_affixes.length > 0) {
    className = 'enchanted-item';
  }

  if (row.item.item_affixes.length > 0 && row.artifact_property !== null) {
    className = 'magical-item';
  }

  const spopover = (
    <Popover id="equipped-item" style={{maxWidth: 500}}>
      <ItemInfo item={row.item} />
    </Popover>
  );

  return (
    <span>
      <OverlayTrigger placement="right" overlay={spopover}>
        <a href="#" className={className}>{row.item.name}</a>
      </OverlayTrigger>
    </span>
  );
}

const questItemNameFormatter = (cell, row) => {

  const spopover = (
    <Popover id="quest-item" style={{maxWidth: 500}}>
      <ItemInfo item={row.item} />
    </Popover>
  );

  return (
    <span>
      <OverlayTrigger placement="right" overlay={spopover}>
        <a href="#" className='quest-item'>{row.item.name}</a>
      </OverlayTrigger>
    </span>
  );
}

const actionsFormatter = (cell, row) => {
  if (row.hasOwnProperty('actions')) {
    return (
      <span>
        <DropdownButton
          drop='left'
          variant="primary"
          title='Actions'
          id='dropdown-button-drop-left'
          key='left'
        >
          <Dropdown.Item data-item-id={row.id} onClick={equipAction}>Equip</Dropdown.Item>
          <Dropdown.Item data-item-id={row.id}>Sell</Dropdown.Item>
          <Dropdown.Item data-item-id={row.id} onClick={destroyAction} className="text-danger">
            Destroy
          </Dropdown.Item>
        </DropdownButton>
      </span>
    );
  }
}

const equipmentActionsFormatter = (cell, row) => {
  if (row.hasOwnProperty('actions')) {
    return (
      <span>
        <DropdownButton
          drop='left'
          variant="primary"
          title='Actions'
          id='dropdown-button-drop-left'
          key='left'
        >
          <Dropdown.Item data-equipment-id={row.id} onClick={unEquipAction}>Unequip</Dropdown.Item>
        </DropdownButton>
      </span>
    );
  }
}
