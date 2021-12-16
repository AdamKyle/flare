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
      cost = Math.floor(cost - cost * (this.props.kingdom.building_cost_reduction + this.props.kingdom.iron_cost_reduction));
    } else {
      cost = Math.floor(cost - cost * this.props.kingdom.building_cost_reduction);
    }

    return cost;
  }

  getPopulationRequired() {
    const building = this.props.building;

    let cost = 0;

    if (building.current_durability === 0) {
      cost = building.level * building.base_population;
    } else {
      cost = (building.level + 1) * building.base_population;
    }

    return Math.floor(cost - cost * (this.props.kingdom.building_cost_reduction + this.props.kingdom.population_cost_reduction));
  }

  getTimeRequired() {
    const building = this.props.building;
    let timeNeeded = 0;

    if (building.current_durability === 0) {
      timeNeeded = building.rebuild_time;
    } else {
      timeNeeded = building.time_increase;
    }

    return Math.floor(timeNeeded - timeNeeded * this.props.kingdom.building_time_reduction);
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
          <p className="mt-3">The negative percentage values come from you training: <a href="/information/passive-skills">Passive Skills</a> which help to reduce
          things like resources needed, population needed and by training the Kingmanship skill to reduce time needed.</p>
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
            <dd>{this.getTimeRequired()} Minutes, (~{this.getHours()} hrs.), (-{(this.props.kingdom.building_time_reduction * 100).toFixed(0)}%)</dd>
          </dl>
        </div>
      </div>
    );
  }
}
