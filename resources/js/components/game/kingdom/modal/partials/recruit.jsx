import React from 'react';
import {Popover, OverlayTrigger} from 'react-bootstrap';

export default class Recruit extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      max: this.props.currentPopulation,
      value: "",
      canRecruit: true,
      loading: false,
    }
  }

  componentDidUpdate(prevProps, prevState) {
    if (prevState.max !== this.state.max) {
      this.setState({
        max: this.props.currentPopulation,
        value: 0,
      });
    }
  }

  amountChange(event) {
    let value = parseInt(event.target.value) || '';

    if (value !== '') {
      if (value > this.state.max) {
        value = this.state.max;
      }
    }

    this.setState({
      value: value,
      canRecruit: this.canRecruit(value)
    }, () => {
      this.props.updateAmount(this.state.value);
    });
  }

  recruitUnits() {
    this.setState({
      canRecruit: false,
      loading: true,
    }, () => {
      axios.post('/api/kingdoms/' + this.props.kingdom.id + '/recruit-units/' + this.props.unit.id, {
        amount: this.state.value,
      }).then((result) => {
        const amount = this.state.value;

        this.setState({
          value: 0,
        }, () => {
          this.props.updateKingdomData(result.data);
          this.props.showUnitRecruitmentSuccess('Recruiting ' + amount + ' ' + this.props.unit.name + '. You can see this in the Unit Queue tab.')
          this.props.close();
        });
      }).catch((err) => {
        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 401) {
            location.reload();
          }

          if (response.status === 429) {
            return this.props.openTimeOutModal();
          }
        }
      });
    });
  }

  canRecruit(value) {

    if (value === "" || value === 0) {
      return false;
    }

    const building = this.props.kingdom.buildings.filter((b) => b.name === this.props.unit.recruited_from.name)[0];

    if (building.current_durability === 0) {
      return false;
    }

    const costTypes = ['required_population', 'wood_cost', 'clay_cost', 'stone_cost', 'iron_cost'];

    let notEnoughTypes = [];

    for (let i = 0; i <= costTypes.length; i++) {

      const kingdomCurrent = this.getKingdomAmount(costTypes[i]);

      const unitTotalCost = this.props.unit[costTypes[i]] * value;

      if (unitTotalCost > kingdomCurrent) {
        notEnoughTypes.push(costTypes[i])
      }
    }

    return notEnoughTypes.length === 0;
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
      <div>
        <hr/>
        <h5>Recruitment</h5>
        <hr/>
        {
          this.props.kingdom.current_population === 0 ?
            <div className="alert alert-danger mt-2 mb-2">You have no population. You cannot recruit</div>
            : null
        }

        {
          !this.props.unit.can_recruit_more ?
            <div className="alert alert-danger mt-2 mb-2">You have recruited as many as you are allowed.</div>
            : null
        }
        <div className="row">
          <div className="col-md-6">
            <dl className="mb-3">
              <dt><strong>Current Population</strong>:</dt>
              <dd>
                {this.state.max}
                <OverlayTrigger
                  trigger="hover"
                  key='right'
                  placement='right'
                  overlay={
                    <Popover id={`popover-positioned-right`}>
                      <Popover.Title as="h3">Current Population</Popover.Title>
                      <Popover.Content>
                        <p>
                          Pay attention to <strong>required population</strong> in the <strong>unit cost</strong> section.
                          The current population here is a total amount of all remaining people in your kingdom. Just because you have X
                          number of people does not mean you can recruit all of those people. Some units have different population requirements.
                        </p>
                      </Popover.Content>
                    </Popover>
                  }
                >
                  <i className="fas fa-question-circle ml-2"></i>
                </OverlayTrigger>
              </dd>
              <dt><strong>Maximum Allowed:</strong>: </dt>
              <dd>
                {this.props.unit.kd_max}
                <OverlayTrigger
                  trigger="hover"
                  key='right'
                  placement='right'
                  overlay={
                    <Popover id={`popover-positioned-right`}>
                      <Popover.Title as="h3">Maximum Allowed</Popover.Title>
                      <Popover.Content>
                        <p>
                          This is the maximum allowed amount for this unit, for this kingdom.
                        </p>
                      </Popover.Content>
                    </Popover>
                  }
                >
                  <i className="fas fa-question-circle ml-2"></i>
                </OverlayTrigger>
              </dd>
            </dl>
            <input
              className="form-control"
              type="number"
              min={0}
              max={this.state.max}
              value={this.state.value}
              onChange={this.amountChange.bind(this)}
            />
          </div>
          <div className="col-md-6">
            <button className="btn btn-primary unit-recruit-button"
                    disabled={!this.state.canRecruit || !this.props.unit.can_recruit_more}
                    onClick={this.recruitUnits.bind(this)}
            >
              Recruit Selected Amount
            </button>
          </div>
        </div>
        {
          this.state.loading ?
            <div className="progress loading-progress mt-3" style={{position: 'relative', width: '100%'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
            : null
        }
        <hr/>
      </div>
    );
  }
}
