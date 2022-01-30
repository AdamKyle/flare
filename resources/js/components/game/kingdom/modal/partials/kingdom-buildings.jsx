import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';

export default class KingdomBuildings extends React.Component {

  constructor(props) {
    super(props);

    this.columns = [
      {
        key: "name",
        text: "Name",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.name}>
            {
              row.is_locked ?
                <span className="text-danger">{row.name} <i className="fas fa-lock"></i></span>
              :
                row.name
            }
          </div>
        </div>,
        sortable: true
      },
      {
        key: "level",
        text: "Level",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.name + '-' + row.level}>
            {
              row.level
            }
          </div>
        </div>,
        sortable: true
      },
      {
        key: "current_durability",
        text: "Current Durability",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{row.current_durability} / {row.max_durability}</div>
        </div>,
        sortable: true
      },
      {
        key: "is_maxed",
        text: "Is Max Level",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{row.level >= row.max_level ? 'Yes' : 'No'}</div>
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

  formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  render() {
    return (
      <div className="row mt-2">
        <div className="col-sm-12 overflow-tabs">
          <h4  className="tw-font-light mt-1">Current Resources</h4>
          <hr />
          <div className="row">
            <div className="col-md-4">
              <dl>
                <dt><strong>Wood</strong>:</dt>
                <dd>{this.formatNumber(this.props.kingdom.current_wood)} / {this.formatNumber(this.props.kingdom.max_wood)}</dd>
              </dl>
            </div>
            <div className="col-md-4">
              <dl>
                <dt><strong>Clay</strong>:</dt>
                <dd>{this.formatNumber(this.props.kingdom.current_clay)} / {this.formatNumber(this.props.kingdom.max_clay)}</dd>
              </dl>
            </div>
            <div className="col-md-4">
              <dl>
                <dt><strong>Stone</strong>:</dt>
                <dd>{this.formatNumber(this.props.kingdom.current_stone)} / {this.formatNumber(this.props.kingdom.max_stone)}</dd>
              </dl>
            </div>
          </div>
          <div className="row">
            <div className="col-md-4">
              <dl>
                <dt><strong>Iron</strong>:</dt>
                <dd>{this.formatNumber(this.props.kingdom.current_iron)} / {this.formatNumber(this.props.kingdom.max_iron)}</dd>
              </dl>
            </div>
            <div className="col-md-4">
              <dl>
                <dt><strong>Pop.</strong>:</dt>
                <dd>{this.formatNumber(this.props.kingdom.current_population)} / {this.formatNumber(this.props.kingdom.max_population)}</dd>
              </dl>
            </div>
          </div>
          <hr />
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
