import React, {Fragment} from 'react';
import ItemName from "../../../../marketboard/components/item-name";
import {Alert, Card} from "react-bootstrap";
import ReactDatatable from "@ashvin27/react-datatable";
import EquippedSectionDropDowns from "./equipped-section-drop-downs";

export default class UsableItemsSection extends React.Component {

  constructor(props) {
    super(props);

    this.usable_config = {
      page_size: 25,
      length_menu: [25, 50, 75],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.usable_headers = [
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
        key: 'description',
        text: 'Description',
      },
      {
        name: "actions",
        text: "Actions",
        cell: row => <Fragment>
          <button className="btn btn-primary" onCLick={() => this.manageUseItem(row)}>Use</button>
        </Fragment>
      },
    ];

    this.state = {
      successMessage: null,
      errorMessage: null,
      manageOpenSaveAsSet: false,
    }
  }

  clearSuccessMessage() {
    this.setState({
      successMessage: null,
    })
  }

  clearErrorMessage() {
    this.setState({
      errorMessage: null,
    })
  }

  setSuccessMessage(message) {
    this.setState({
      successMessage: message,
    })
  }

  setErrorMessage(message) {
    this.setState({
      errorMessage: message,
    })
  }

  formatDataForTable() {
    return this.props.usableItems.map((ui) => ui.item);
  }

  findItemSlotId(itemId) {
    const found = this.props.usableItems.filter((ui) => ui.item.id === itemId);

    if (found.length > 0) {
      return found[0].id
    }
  }

  manageUseMany() {

  }

  manageUseItem(item) {

  }

  render() {
    return(
      <Card>
        <Card.Body>
          <button className='btn btn-primary mr-2 mt-2'
                  disabled={this.props.usableItems.length === 0}
                  onClick={this.manageUseMany.bind(this)}
          >
            Use Many
          </button>
          <hr />
          <ReactDatatable
            config={this.usable_config}
            records={this.formatDataForTable()}
            columns={this.usable_headers}
          />
        </Card.Body>
      </Card>
    )
  }
}