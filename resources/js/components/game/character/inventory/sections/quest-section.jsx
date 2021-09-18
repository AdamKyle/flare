import React, {Fragment} from 'react';
import ItemName from "../../../../marketboard/components/item-name";
import {Alert, Card} from "react-bootstrap";
import ReactDatatable from "@ashvin27/react-datatable";
import EquippedSectionDropDowns from "./equipped-section-drop-downs";

export default class QuestSection extends React.Component {

  constructor(props) {
    super(props);

    this.quest_items_config = {
      page_size: 10,
      length_menu: [10, 25, 50, 75],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.quest_items_headers = [
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
    ];
  }

  formatDataForTable() {
    return this.props.questItems.map((q) => q.item);
  }

  render() {
    return(
      <Card>
        <Card.Body>
          <ReactDatatable
            config={this.quest_items_config}
            records={this.formatDataForTable()}
            columns={this.quest_items_headers}
          />
        </Card.Body>
      </Card>
    )
  }


}