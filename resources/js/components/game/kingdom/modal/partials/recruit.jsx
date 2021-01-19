import React from 'react';
import RangeSlider from 'react-bootstrap-range-slider';

export default class Recruit extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            max: this.props.currentPopulation,
            value: 0,
            canRecruit: false,
        }
    }

    componentDidUpdate() {
        if (this.props.currentPopulation !== this.state.max) {
            this.setState({
                max: this.props.currentPopulation,
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
        axios.post('/api/kingdoms/'+this.props.kingdom.id+'/recruit-units/' + this.props.unit.id, {
            amount: this.state.value,
        }).then((result) => {
            this.setState({
                value: 0,
                canRecruit: false,
            }, () => {
                this.props.updateKingdomData(result.data);
                this.props.close();
            });
        }).catch((err) => {
            console.log(err);
        });
    }

    canRecruit(value) {
        
        if (value === 0) {
            return false;
        }

        const costTypes = ['required_population', 'wood_cost', 'clay_cost', 'stone_cost', 'iron_cost'];
        
        for (const i = 0; i <= costTypes.length; i++) {

            const kingdomCurrent = this.getKingdomAmount(costTypes[i]);

            if (kingdomCurrent === 0) {
                return false;
            }

            const unitTotalCost = this.props.unit[costTypes[i]] * value;

            if (unitTotalCost > kingdomCurrent) {
                return false;
            }

            if (unitTotalCost < kingdomCurrent) {
                return true;
            }

            return true;
        }
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
                <hr />
                <h5>Recruitment</h5>
                <hr />
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
                        <button className="btn btn-primary mt-2" disabled={this.state.canRecruit ? false : true} onClick={this.recruitUnits.bind(this)}>Recruit Selected Amount</button>
                    </div>
                </div>
                <hr />
            </div>
        );
    }
}