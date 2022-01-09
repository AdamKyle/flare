import React, {Fragment} from 'react';
import {Popover, OverlayTrigger} from 'react-bootstrap';
import AlertInfo from "../../../components/base/alert-info";

export default class Recruit extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      max: parseInt(this.props.unit.kd_max.replace(/,/g, '')) || 0,
      value: "",
      canRecruit: true,
      loading: false,
      recruitmentType: '',
      totalCost: 0,
    }
  }

  componentDidUpdate(prevProps, prevState) {
    if (prevState.max !== this.state.max) {
      this.setState({
        max: parseInt(this.props.unit.kd_max.replace(/,/g, '')) || 0,
        value: 0,
      });
    }
  }

  amountChange(event) {
    let value = parseInt(event.target.value) || 0;

    if (value !== 0) {
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

  amountChangeWithGold(event) {
    let value = parseInt(event.target.value) || 0;

    if (value !== 0) {
      if (value > this.state.max) {
        value = this.state.max;
      }
    }

    const costReduction = this.props.kingdom.unit_cost_reduction;
    let   totalCost     = value * this.props.unit.cost_per_unit;

    totalCost = totalCost - Math.floor(totalCost * costReduction);

    this.setState({
      value: value,
      canRecruit: this.canRecruitWithGold(value) && value > 0,
      totalCost: totalCost,
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
        recruitment_type: this.state.recruitmentType,
        total_cost: this.state.totalCost
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
      let costReduction    = this.props.kingdom.unit_cost_reduction;

      if (costTypes[i] === 'iron_cost') {
        costReduction += this.props.kingdom.iron_cost_reduction;
      }

      if (costTypes[i] === 'required_population') {
        costReduction += this.props.kingdom.population_cost_reduction;
      }

      let unitTotalCost = this.props.unit[costTypes[i]] * value;

      unitTotalCost = unitTotalCost - Math.floor(unitTotalCost * costReduction);

      if (unitTotalCost > kingdomCurrent) {
        notEnoughTypes.push(costTypes[i])
      }
    }

    return notEnoughTypes.length === 0;
  }

  canRecruitWithGold(value) {
    let cost = this.props.unit.cost_per_unit;

    cost *= value;

    const costReduction = this.props.kingdom.unit_cost_reduction;

    cost = cost - Math.floor(cost * costReduction);

    const characterGold = parseInt(this.props.characterGold.replace(/,/g, ''));

    return characterGold >= cost;
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

  handleRecruitmentType(event) {
    this.setState({
      recruitmentType: event.target.value,
      value: 0,
      canRecruit: false,
    });
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
          <div className="col-md-12">
            <dl className="mb-3">
              <dt><strong>Current Population</strong>:</dt>
              <dd>
                {this.state.max.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}
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
              <dt><strong>Maximum Allowed:</strong> </dt>
              <dd>
                {this.props.unit.kd_max.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}
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
              <dt>Cost Reduction %:</dt>
              <dd>{this.props.kingdom.unit_cost_reduction * 100}%</dd>
            </dl>
            <div className="form-group">
              <label htmlFor="unit-recruitment-type">Recruitment Type</label>
              <select className="form-control" id="unit-recruitment-type" value={this.state.recruitmentType} onChange={this.handleRecruitmentType.bind(this)}>
                <option value={''}>Please select</option>
                <option value={'recruit-normally'}>Recruit Normally (Costs Resources)</option>
                <option value={'recruit-with-gold'}>Recruit with gold</option>
              </select>
            </div>
            {
              this.state.recruitmentType === 'recruit-normally' ?
                <Fragment>
                  <input
                    className="form-control"
                    type="number"
                    name="recruit-normally-amount"
                    min={0}
                    max={this.state.max}
                    value={this.state.value}
                    onChange={this.amountChange.bind(this)}
                  />

                  <button className="btn btn-primary mt-3"
                          disabled={!this.state.canRecruit || !this.props.unit.can_recruit_more || this.state.recruitmentType === ''}
                          onClick={this.recruitUnits.bind(this)}
                  >
                    Recruit Selected Amount
                  </button>
                </Fragment>
              : null
            }

            {
              this.state.recruitmentType === 'recruit-with-gold' ?
                <Fragment>
                  <AlertInfo icon={'fas fa-question-circle'} title="Info">
                    <p>You can pay gold instead of resources to recruit units.</p>
                    <p>You cannot buy more units then your population allows. You also cannot buy
                    more units then you have gold. Each unit has a cost per unit.</p>
                    <p>Recruitment time still counts. The more you recruit the more time it takes.</p>
                    <p>When recruiting with gold, you do not need to worry about the resource cost section, this is just an idea of how many resources it would take
                    to recruit the units you wanted, if you were using resources.</p>
                    <p>Do not use gold to purchase large amounts on units, until you have trained your Kingmanship <a href="/information/skill-information" target="_blank">Skill</a>&nbsp;
                      to a significant portion. For example - with Kingmanship at level 1, recruiting 1 billion spearmen would take: <strong>94 years in real time</strong>,
                      with the skill maxed out, it takes 34.72 days to recruit that many units, which is <em>much</em> better.</p>
                  </AlertInfo>
                  <dl className="mt-3 mb-3">
                    <dt>Cost per unit:</dt>
                    <dd>{this.props.unit.cost_per_unit}</dd>
                    <dt>Your Gold:</dt>
                    <dd>{this.props.characterGold}</dd>
                    <dt>Current Price:</dt>
                    <dd className={!this.state.canRecruit ? 'text-danger' : 'text-success'}>{this.state.totalCost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</dd>
                  </dl>
                  <input
                    className="form-control"
                    name="recruit-normally-amount"
                    type="number"
                    min={0}
                    max={this.state.max}
                    value={this.state.value}
                    onChange={this.amountChangeWithGold.bind(this)}
                  />

                  <button className="btn btn-primary mt-3"
                          disabled={!this.state.canRecruit || !this.props.unit.can_recruit_more || this.state.recruitmentType === ''}
                          onClick={this.recruitUnits.bind(this)}
                  >
                    Recruit Selected Amount
                  </button>
                </Fragment>
                : null
            }

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
