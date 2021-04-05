import React from 'react';
import _ from 'lodash';
import {
  Accordion,
  Card,
  Button
} from 'react-bootstrap';
import {
  time
} from '../../../../helpers/distance_calculations';

export default class UnitSelection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      unitsToSend: {},
      loading: true,
      totalTime: 0,
      totalAmount: 0,
    }
  }

  componentDidMount() {
    let unitsToSend = {};

    this.props.selectedKingdomData.forEach((kingdom) => {
      if (kingdom.units.length > 0) {
        unitsToSend[kingdom.kingdom_name] = {};

        kingdom.units.forEach((unit) => {
          unitsToSend[kingdom.kingdom_name][unit.name] = {
            amount_to_send: 0,
            max_amount: unit.amount,
            total_time: 0
          }
        });
      }
    });

    this.setState({
      unitsToSend: unitsToSend,
      loading: false,
    });
  }

  handleChange(event) {
    event.persist();

    let kingdomName = event.target.getAttribute('data-kingdom-name');
    let unitName = event.target.getAttribute('data-unit-name');
    let amount = parseInt(event.target.value) || '';
    let unitsToSend = _.cloneDeep(this.state.unitsToSend);
    let maxAmount = unitsToSend[kingdomName][unitName]['max_amount'];

    unitsToSend[kingdomName][unitName]['amount_to_send'] = amount;

    if (amount > maxAmount) {
      unitsToSend[kingdomName][unitName]['amount_to_send'] = maxAmount;
    } else if (amount < 0) {
      unitsToSend[kingdomName][unitName]['amount_to_send'] = 0;
    }

    if (amount === 0) {
      unitsToSend[kingdomName][unitName]['total_time'] = 0;
    } else {
      const time = this.getTimeForUnits(kingdomName, unitName);

      unitsToSend[kingdomName][unitName]['total_time'] = time;
    }

    const totalAmount = this.getTotalAmount(unitsToSend)

    this.setState({
      unitsToSend: unitsToSend,
      totalTime: this.getTotalTime(unitsToSend),
      totalAmount: totalAmount,
    }, () => {
      this.props.enableAttack((totalAmount > 0));
      this.props.setUnitsToSendValue(unitsToSend);
    });
  }

  getTimeForUnits(kingdomName, unitName) {

    let foundKingdom = this.props.selectedKingdomData.filter((k) => k.kingdom_name === kingdomName);

    if (_.isEmpty(foundKingdom)) {
      return 0;
    }

    foundKingdom = foundKingdom[0];

    let foundUnit = foundKingdom.units.filter((u) => u.name === unitName);

    if (_.isEmpty(foundUnit)) {
      return 0;
    }

    foundUnit = foundUnit[0];

    return (time(this.props.defendingKingdom.x_position, this.props.defendingKingdom.y_position, foundKingdom.x_position, foundKingdom.y_position) * foundUnit.travel_time);
  }

  getTotalTime(unitsToSend) {
    let totalTime = 0;

    for (const kingdom in unitsToSend) {
      for (const unit in unitsToSend[kingdom]) {
        totalTime += unitsToSend[kingdom][unit].total_time;
      }
    }

    return totalTime;
  }

  getTotalAmount(unitsToSend) {
    let totalAmount = 0;

    for (const kingdom in unitsToSend) {
      for (const unit in unitsToSend[kingdom]) {
        const amount = unitsToSend[kingdom][unit].amount_to_send;

        if (amount !== '') {
          totalAmount += amount;
        }
      }
    }

    return totalAmount;
  }

  getTravelTimeFromKingdom(unitsForKingdom) {
    let totalTime = 0;

    for (const unit in unitsForKingdom) {
      totalTime += unitsForKingdom[unit].total_time;
    }

    return totalTime;
  }

  renderUnitSelection(units, kingdomName) {
    return units.map((unit) => {
      return (
        <div className="form-group mb-2" key={unit.name}>
          <label htmlFor={unit.name}>{unit.name} (Max: {unit.amount})</label>
          <input
            type="number"
            steps="1"
            min="0"
            max={this.state.unitsToSend[kingdomName][unit.name]['max_amount']}
            className="form-control"
            id={unit.name}
            data-kingdom-name={kingdomName}
            data-unit-name={unit.name}
            max={unit.amount}
            value={this.state.unitsToSend[kingdomName][unit.name]['amount_to_send']}
            onChange={this.handleChange.bind(this)}
          />
        </div>
      );
    });
  }

  renderKingdomsAccordions() {
    return this.props.selectedKingdomData.map((kingdom) => {
      if (kingdom.units.length > 0) {
        return (
          <Card key={kingdom.kingdom_name}>
            <Card.Header>
              <Accordion.Toggle as={Button} variant="link" eventKey={kingdom.kingdom_name}>
                {kingdom.kingdom_name}
              </Accordion.Toggle>
            </Card.Header>
            <Accordion.Collapse eventKey={kingdom.kingdom_name}>
              <Card.Body>
                {this.renderUnitSelection(kingdom.units, kingdom.kingdom_name)}
                <hr/>
                <dl className="mt-2">
                  <dt><strong>Kingdom To Attack (X/Y)</strong>:</dt>
                  <dd>{this.props.defendingKingdom.x_position}/{this.props.defendingKingdom.y_position}</dd>
                  <dt><strong>Curent Position (X/Y)</strong>:</dt>
                  <dd>{kingdom.x_position}/{kingdom.y_position}</dd>
                  <dt><strong>Total Travel Time</strong>:</dt>
                  <dd>{this.getTravelTimeFromKingdom(this.state.unitsToSend[kingdom.kingdom_name])} Minutes</dd>
                </dl>
              </Card.Body>
            </Accordion.Collapse>
          </Card>
        );
      }
    });
  }

  render() {
    return (
      <div className="container">
        {
          this.state.loading ?
            <div className="progress" style={{position: 'relative'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
            :
            <>
              <div className="alert alert-info">
                Only showing kingdoms who have units that you can send out.
              </div>

              <Accordion defaultActiveKey="0">
                {this.renderKingdomsAccordions()}
              </Accordion>

              <div className="mt-3">
                <dl>
                  <dt><strong>Kingdom To Attack (X/Y)</strong>:</dt>
                  <dd>{this.props.defendingKingdom.x_position}/{this.props.defendingKingdom.y_position}</dd>
                  <dt><strong>Time Till Desitnation</strong>:</dt>
                  <dd>{this.state.totalTime} Minutes (across all kingdoms)</dd>
                  <dt><strong>Total Units To Send</strong>:</dt>
                  <dd>{this.state.totalAmount}</dd>
                </dl>
              </div>
            </>
        }
      </div>
    );
  }
}
