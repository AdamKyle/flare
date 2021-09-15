import React, {Fragment} from 'react';
import {Card, Alert} from 'react-bootstrap';
import ReactDatatable from '@ashvin27/react-datatable';
import ItemName from "../../../../marketboard/components/item-name";
import InventorySectionDropDowns from "./inventory-section-drop-downs";
import DestroyModal from "../modals/destroy-modal";

export default class InventorySection extends React.Component {

  constructor(props) {
    super(props);

    this.inventory_config = {
      page_size: 10,
      length_menu: [10, 25, 75],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.inventory_headers = [
      {
        key: "affix_name",
        text: "Name",
        sortable: true,
        cell: row => <div>
          <a href={'/items/' + row.id} target="_blank">
            <ItemName item={row} useAffixName={true} />
          </a>
        </div>
      },
      {
        key: "type",
        text: "Type",
        sortable: true,
      },
      {
        key: 'base_damages',
        text: 'Base Damage',
        sortable: true,
        cell: row => <div>{row.base_damage !== null ? row.base_damage : 0}</div>
      },
      {
        key: 'base_ac',
        text: 'Base AC',
        sortable: true,
        cell: row => <div>{row.base_ac !== null ? row.base_ac : 0}</div>
      },
      {
        key: 'base_healing',
        text: 'Base Healing',
        sortable: true,
        cell: row => <div>{row.base_healing !== null ? row.base_healing : 0}</div>
      },
      {
        name: "actions",
        text: "Actions",
        cell: row => <Fragment>
          <InventorySectionDropDowns
            characterId={this.props.characterId}
            item={row}
            getSlotId={this.props.getSlotId}
            manageDestroyModal={this.manageDestroyModal.bind(this)}
            manageSetModal={this.manageSetModal.bind(this)}
          />
        </Fragment>
      },
    ];

    this.state = {
      successMessage: null,
      showDestroyModal: false,
      showSetModal: false,
      itemForDestroy: null,
    }
  }

  formatDataForTable() {
    return this.props.inventory.map((i) => i.item);
  }

  clearSuccessMessage() {
    this.setState({
      successMessage: null,
    })
  }

  manageDestroyModal(item, successMessage) {
    this.setState({
      showDestroyModal: !this.state.showDestroyModal,
      itemForDestroy: typeof item !== 'undefined' ? item : null,
    }, () => {
      this.setState({
        successMessage: typeof successMessage !== 'undefined' ? successMessage : null,
      })
    });
  }

  manageSetModal() {
    this.setState({
      showSetModal: !this.state.showSetModal
    });
  }

  render() {
    return(
      <Card>
        <Card.Body>
          <div className="alert alert-info mt-2 mb-3">
            <p>
              Destroying items will destroy all items and give you no gold dust, even for enchanted items.
              Disenchanting will use your skill to determine if you succeed or not. If you succeed you can get between 1-150 Gold dust
              + your skill bonus on top.
            </p>

            <p>
              You can assign items to sets by clicking the action drop down and assigning the item to a set. You can also choose
              to equip all items save them as a set from the equip tab.
            </p>

            <p>Clicking the name will open the item details in a new window</p>
          </div>
          {
            this.state.successMessage !== null ?
              <div className="mb-3">
                <Alert variant="success" onClose={this.clearSuccessMessage.bind(this)} dismissible>
                  {this.state.successMessage}
                </Alert>
              </div>
            : null
          }
          <hr />
            <button className='btn btn-danger mr-2'>Destroy All</button>
            <button className='btn btn-primary mr-2'>Disenchant All</button>
          <hr />
          <ReactDatatable
            config={this.inventory_config}
            records={this.formatDataForTable()}
            columns={this.inventory_headers}
          />

          {
            this.state.showDestroyModal && this.state.itemForDestroy !== null ?
              <DestroyModal
                characterId={this.props.characterId}
                item={this.state.itemForDestroy}
                getSlotId={this.props.getSlotId}
                open={this.state.showDestroyModal}
                close={this.manageDestroyModal.bind(this)}
              />
              : null
          }
        </Card.Body>
      </Card>
    )
  }
}