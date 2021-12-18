import React from 'react';
import {OverlayTrigger, Popover} from 'react-bootstrap';

export default class UnitData extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      invalidResources: false,
    };
  }

  calculateAmount(prop, amount) {
    if (amount === 0 || amount === '') {
      return this.props.unit[prop];
    }

    let totalCost = this.props.unit[prop] * amount;

    if (prop === 'required_population') {
      totalCost = totalCost - totalCost * this.props.kingdom.population_cost_reduction;
    }

    if (prop === 'iron') {
      totalCost = totalCost - totalCost * this.props.kingdom.iron_cost_reduction;
    }

    if (prop === 'time_to_recruit') {
      totalCost = totalCost - totalCost * this.props.kingdom.unit_time_reduction;
    }

    return totalCost.toFixed(2);
  }

  getClass(prop, amount) {
    if (amount === 0 || amount === '') {
      return '';
    }

    if (this.props.hasOwnProperty('isQueue')) {
      return '';
    }

    const currentAmount = this.getKingdomAmount(prop);

    if (currentAmount !== 0) {
      const totalCost = this.props.unit[prop] * amount;

      if (totalCost > currentAmount) {

        return 'text-danger';
      }

      if (totalCost < currentAmount) {
        return 'text-success';
      }

      if (totalCost === currentAmount) {
        return 'text-success';
      }
    }

    return 'text-success';
  }

  getKingdomAmount(prop) {
    switch (prop) {
      case 'wood_cost':
        return this.props.kingdom.current_wood;
      case 'clay_cost':
        return this.props.kingdom.current_clay;
      case 'stone_cost':
        return this.props.kingdom.current_stone;
      case 'iron_cost':
        return this.props.kingdom.current_iron;
      case 'required_population':
        return this.props.kingdom.current_population;
      default:
        return 0;
    }
  }

  render() {
    return (
      <div className="mt-2">
        <p><strong>Recruited from</strong>: {this.props.unit.recruited_from.name}</p>
        <hr/>
        <div className="row">
          <div className="col-md-6">
            <h5>Unit Stats</h5>
            <hr/>
            <dl>
              <dd><strong>Attack</strong>:</dd>
              <dd
                className={this.getClass('attack', this.props.amount)}>{this.calculateAmount('attack', this.props.amount)}</dd>
              <dd><strong>Defence</strong>:</dd>
              <dd
                className={this.getClass('defence', this.props.amount)}>{this.calculateAmount('defence', this.props.amount)}</dd>
              <dd><strong>Healing Percentage</strong>:</dd>
              <dd>{this.props.unit.heal_percentage * 100}%
                <OverlayTrigger
                  trigger="hover"
                  key='right'
                  placement='right'
                  overlay={
                    <Popover id={`popover-positioned-right`}>
                      <Popover.Title as="h3">Healing Percentage</Popover.Title>
                      <Popover.Content>
                        <p>The percentage shown is for one unit. One unit will heal the total percentage of all
                          units.</p>
                        <p>Stacking (sending multiple) will increase healing amount.</p>
                      </Popover.Content>
                    </Popover>
                  }
                >
                  <i className="fas fa-question-circle ml-2"></i>
                </OverlayTrigger>
              </dd>
              <dd><strong>Is Siege Weapon?</strong>:</dd>
              <dd>{this.props.unit.seige_weapon ? 'Yes' : 'No'}</dd>
              <dd><strong>Can Heal?</strong>:</dd>
              <dd>{this.props.unit.can_heal ? 'Yes' : 'No'}</dd>
            </dl>
          </div>
          <div className="col-md-6">
            <h5>Unit Cost</h5>
            <hr/>
            <dl>
              <dd><strong>Cost in wood</strong>:</dd>
              <dd
                className={this.getClass('wood_cost', this.props.amount)}>{this.calculateAmount('wood_cost', this.props.amount)}</dd>
              <dd><strong>Cost in clay</strong>:</dd>
              <dd
                className={this.getClass('clay_cost', this.props.amount)}>{this.calculateAmount('clay_cost', this.props.amount)}</dd>
              <dd><strong>Cost in stone</strong>:</dd>
              <dd
                className={this.getClass('stone_cost', this.props.amount)}>{this.calculateAmount('stone_cost', this.props.amount)}</dd>
              <dd><strong>Cost in iron</strong>:</dd>
              <dd
                className={this.getClass('iron_cost', this.props.amount)}>{this.calculateAmount('iron_cost', this.props.amount)} (-{(this.props.kingdom.iron_cost_reduction * 100).toFixed()}%)</dd>
              <dd><strong>Required population</strong>:</dd>
              <dd
                className={this.getClass('required_population', this.props.amount)}>
                {this.calculateAmount('required_population', this.props.amount)} (-{(this.props.kingdom.population_cost_reduction * 100).toFixed()}%)
                <OverlayTrigger
                  trigger="hover"
                  key='right'
                  placement='right'
                  overlay={
                    <Popover id={`popover-positioned-right`}>
                      <Popover.Title as="h3">Required Population</Popover.Title>
                      <Popover.Content>
                        <p>This number will increase as you recruit more and more units. This represents how many people are needed for one unit and is then, much like resources,
                        multiplied by amount of people.</p>
                      </Popover.Content>
                    </Popover>
                  }
                >
                  <i className="fas fa-question-circle ml-2"></i>
                </OverlayTrigger>
              </dd>
            </dl>
          </div>
        </div>
        <hr/>
        <div className="row">
          <div className="col-md-6">
            <h5>Time Per Unit</h5>
            <hr/>
            <dl>
              <dd><strong>Travel Time</strong>:</dd>
              <dd>{this.props.unit.travel_time} Minutes(s)</dd>
              <dd><strong>Time To Recruit</strong>:</dd>
              <dd
                className={this.getClass('time_to_recruit', this.props.amount)}>{this.calculateAmount('time_to_recruit', this.props.amount)} Seconds <small
                className="text-muted">{(this.calculateAmount('time_to_recruit', this.props.amount) / 60).toFixed(2)} Minutes (-{(this.props.kingdom.unit_time_reduction * 100).toFixed(0)}%)</small>
              </dd>
            </dl>
          </div>
          <div className="col-md-6">
            <h5>Misc. Details</h5>
            <hr/>
            <dl>
              <dd><strong>Is Attacker?</strong>:</dd>
              <dd>{this.props.unit.attacker ? 'Yes' : 'No'}</dd>
              <dd><strong>Is Defender?</strong>:</dd>
              <dd>{this.props.unit.defender ? 'Yes' : 'No'}</dd>
            </dl>
          </div>
        </div>
      </div>
    );
  }
}
