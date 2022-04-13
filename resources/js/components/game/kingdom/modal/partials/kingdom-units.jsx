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
        key: "current-amount",
        text: "Current Amount",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{this.getCurrentUnitAmount(row.id)}</div>
        </div>,
        sortable: true
      },
      {
        key: "max-amount",
        text: "Max Recruitable Amount",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{this.props.kingdom.current_population.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</div>
        </div>,
        sortable: true
      },
      {
        key: "attack",
        text: "Attack",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{this.getAttack(row.id)}</div>
        </div>,
        sortable: true
      },
      {
        key: "defence",
        text: "Defence",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{this.getDefence(row.id)}</div>
        </div>,
        sortable: true
      },
      {
        key: "wood_cost",
        text: "Wood Cost",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{this.getCost(row.id, 'wood')}</div>
        </div>,
        sortable: true
      },
      {
        key: "clay_cost",
        text: "Clay Cost",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{this.getCost(row.id, 'clay')}</div>
        </div>,
        sortable: true
      },
      {
        key: "stone_cost",
        text: "Stone Cost",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{this.getCost(row.id, 'stone')}</div>
        </div>,
        sortable: true
      },
      {
        key: "iron_cost",
        text: "Iron Cost",
        cell: row => <div data-tag="allowRowEvents">
          <div key={row.id}>{this.getCost(row.id, 'iron')}</div>
        </div>,
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

  getCurrentUnitAmount(unitId) {
    if (_.isEmpty(this.props.kingdom.current_units)) {
      return 0;
    }

    const units = this.props.kingdom.current_units.filter((cu) => cu.game_unit_id === unitId);

    if (units.length > 0) {
      return units[0].amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    return 0;
  }

  getDefence(unitId) {
    const units = this.props.kingdom.recruitable_units.filter((ru) => ru.id === unitId);

    if (units.length > 0) {
      return units[0].defence;
    }

    return 0;
  }

  getAttack(unitId) {
    const units = this.props.kingdom.recruitable_units.filter((ru) => ru.id === unitId);

    if (units.length > 0) {
      return units[0].attack;
    }

    return 0;
  }

  getCost(unitId, type) {
    const units = this.props.kingdom.recruitable_units.filter((ru) => ru.id === unitId);

    if (units.length > 0) {
      return units[0][type + '_cost'];
    }

    return 0;
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
            records={this.props.kingdom.recruitable_units}
            columns={this.columns}
            onRowClicked={this.props.recruitUnit}
          />
        </div>
      </div>
    )
  }
}
