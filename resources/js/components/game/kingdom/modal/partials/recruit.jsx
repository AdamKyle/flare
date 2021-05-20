import React from 'react';
import RangeSlider from 'react-bootstrap-range-slider';

export default class Recruit extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      max: Math.round(this.props.currentPopulation / this.props.unit.required_population),
      value: 0,
      canRecruit: false,
    }
  }

  componentDidUpdate(prevProps, prevState) {
    if (prevState.max !== this.state.max) {
      this.setState({
        max: Math.round(this.props.currentPopulation / this.props.unit.required_population),
        value: 0,
      });
    }
  }

  amountChange(event) {
    const value = parseInt(event.target.value);

    this.setState({
      value: value,
      canRecruit: this.canRecruit(value)
    }, () => {
      this.props.updateAmount(this.state.value);
    });
  }

  recruitUnits() {
    axios.post('/api/kingdoms/' + this.props.kingdom.id + '/recruit-units/' + this.props.unit.id, {
      amount: this.state.value,
    }).then((result) => {
      const amount = this.state.value;

      this.setState({
        value: 0,
        canRecruit: false,
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
      }
    });
  }

  canRecruit(value) {

    if (value === 0) {
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
        <div className="row">
          <div className="col-md-6">
            <p><strong>Current Population</strong>: {this.state.max}</p>
            <RangeSlider
              value={this.state.value}
              onChange={this.amountChange.bind(this)}
              min={0}
              max={this.state.max}
              size='lg'
              tooltipPlacement='bottom'
              tooltip='on'
            />
          </div>
          <div className="col-md-6">
            <button className="btn btn-primary mt-2" disabled={this.state.canRecruit ? false : true}
                    onClick={this.recruitUnits.bind(this)}>Recruit Selected Amount
            </button>
          </div>
        </div>
        <hr/>
      </div>
    );
  }
}
