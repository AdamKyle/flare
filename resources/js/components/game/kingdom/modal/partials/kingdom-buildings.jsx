import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';

export default class KingdomBuildings extends React.Component {

  constructor(props) {
    super(props);

    this.columns = [
      {
        key: "name",
        text: "Name",
        sortable: true
      },
      {
        key: "level",
        text: "Level",
        sortable: true
      },
      {
        key: "current_durability",
        text: "Current Durability",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{row.current_durability}</div>
        </div>,
        sortable: true
      },
      {
        key: "current_defence",
        text: "Current Defence",
        sortable: true
      },
      {
        key: "wood_cost",
        text: "Wood Cost",
        sortable: true
      },
      {
        key: "clay_cost",
        text: "Clay Cost",
        sortable: true
      },
      {
        key: "stone_cost",
        text: "Stone Cost",
        sortable: true
      },
      {
        key: "iron_cost",
        text: "Iron Cost",
        sortable: true
      },
    ];

    this.config = {
      page_size: 5,
      length_menu: [5, 10, 25],
      show_filter: true,
      show_pagination: true,
      pagination: 'advance',
    }
  }

  render() {
    return (
      <div className="row mt-2">
        <div className="col-sm-12 overflow-tabs">
          <ReactDatatable
            config={this.config}
            records={this.props.kingdom.buildings}
            columns={this.columns}
            onRowClicked={this.props.rowClickedHandler}
          />
        </div>
      </div>
    )
  }
}
