import React from 'react';

export default class BuildingCostSection extends React.Component {
  constructor(props) {
    super(props);
  }

  getCost(type) {
    const building = this.props.building;

    let cost = 0;

    if (building.current_durability === 0) {
      cost = building.level * building['base_' + type + '_cost'];
    } else {
      cost = building[type + '_cost'];
    }

    if (type === 'iron') {
      cost = Math.floor(cost - cost * this.props.kingdom.building_cost_reduction + this.props.kingdom.iron_cost_reduction);
    } else if (type === 'population') {
      console.log(cost);
      cost = Math.floor(cost - cost * this.props.kingdom.building_cost_reduction + this.props.kingdom.population_cost_reduction);
    } else {
      cost = Math.floor(cost - cost * this.props.kingdom.building_cost_reduction);
    }

    return cost;
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

  getHours() {
    return ((1/60) * this.getTimeRequired()).toFixed(2);
  }

  getIronDeduction() {
    return ((this.props.kingdom.building_cost_reduction + this.props.kingdom.iron_cost_reduction) * 100).toFixed(0);
  }

  getPopulationDeduction() {
    return ((this.props.kingdom.building_cost_reduction + this.props.kingdom.population_cost_reduction) * 100).toFixed(0);
  }

  getDeduction() {
    return (this.props.kingdom.building_cost_reduction * 100).toFixed(0);
  }

  render() {
    return (
      <div className="row">
        <div className="col-md-6">
          <dl>
            <dt><strong>Wood Cost</strong>:</dt>
            <dd>{this.getCost('wood')} (-{this.getDeduction()}%)</dd>
            <dt><strong>Clay Cost</strong>:</dt>
            <dd>{this.getCost('clay')} (-{this.getDeduction()}%)</dd>
            <dt><strong>Stone Cost</strong>:</dt>
            <dd>{this.getCost('stone')} (-{this.getDeduction()}%)</dd>
            <dt><strong>Iron Cost</strong>:</dt>
            <dd>{this.getCost('iron')} (-{this.getIronDeduction()}%)</dd>
            <dt><strong>Population Cost</strong>:</dt>
            <dd>{this.getPopulationRequired()} (-{this.getPopulationDeduction()}%)</dd>
          </dl>
          <p className="mt-3">The negative percentage values come from you training: Building Management <a href="/information/passive-skills">Passive</a>.</p>
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
            <dd>{this.getTimeRequired()} Minutes, <span>(~{this.getHours()} hrs.)</span></dd>
          </dl>
        </div>
      </div>
    );
  }
}
