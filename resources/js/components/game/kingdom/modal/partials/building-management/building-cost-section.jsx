import React from 'react';

export default class BuildingCostSection extends React.Component {
  constructor(props) {
    super(props);
  }

  getCost(type) {
    const building = this.props.building;

    if (building.current_durability === 0) {
      return building.level * building['base_' + type + '_cost'];
    }

    return building[type + '_cost'];
  }

  getPopulationRequired() {
    const building = this.props.building;

    if (building.current_durability === 0) {
      return building.level * building.base_population;
    }

    return building.population_required;
  }

  getTimeRequired() {
    const building = this.props.building;

    if (building.current_durability === 0) {
      return building.rebuild_time;
    }

    return building.time_increase;
  }

  render() {
    return (
      <div className="row">
        <div className="col-md-6">
          <dl>
            <dt><strong>Wood Cost</strong>:</dt>
            <dd>{this.getCost('wood')}</dd>
            <dt><strong>Clay Cost</strong>:</dt>
            <dd>{this.getCost('clay')}</dd>
            <dt><strong>Stone Cost</strong>:</dt>
            <dd>{this.getCost('stone')}</dd>
            <dt><strong>Iron Cost</strong>:</dt>
            <dd>{this.getCost('iron')}</dd>
            <dt><strong>Population Cost</strong>:</dt>
            <dd>{this.getPopulationRequired()}</dd>
          </dl>
        </div>
        <div className="col-md-6">
          <dl>
            <dt><strong>Can Upgrade</strong>:</dt>
            <dd>{this.props.canUpgrade && this.props.building.current_durability !== 0 ? 'Yes' : 'No'}</dd>
            <dt><strong>Needs Repair</strong>:</dt>
            <dd>{this.props.building.current_durability === 0 ? 'Yes' : 'No'}</dd>
            <dt>
              <strong>
                {
                  this.props.building.current_durability === 0 ?
                    'Rebuild Time'
                    :
                    'Upgrade Time'
                }
              </strong>:
            </dt>
            <dd>{this.getTimeRequired()} Minutes</dd>
          </dl>
        </div>
      </div>
    );
  }
}
