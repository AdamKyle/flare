import React   from 'react';
import _       from 'lodash';
import {
    Accordion, 
    Card,
    Button
}              from 'react-bootstrap';

export default class UnitSelection extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            unitsToSend: {},
            loading: true,
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
                        has_error: false,
                        max_amount: unit.amount,
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
        let unitName    = event.target.getAttribute('data-unit-name');
        let amount      = parseInt(event.target.value);
        let unitsToSend = _.cloneDeep(this.state.unitsToSend);
        let maxAmount   = unitsToSend[kingdomName][unitName]['max_amount'];

        unitsToSend[kingdomName][unitName]['amount_to_send'] = amount;

        if (amount > maxAmount) {
            unitsToSend[kingdomName][unitName]['has_error'] = true;
        } else {
            unitsToSend[kingdomName][unitName]['has_error'] = false;
        }

        this.setState({
            unitsToSend: unitsToSend
        });
    }

    renderUnitSelection(units, kingdomName) {
        return units.map((unit) => {
            return (
                <div className="form-group mb-2" key={unit.name}>
                    {
                        this.state.unitsToSend[kingdomName][unit.name]['has_error'] ?
                            <div className="alert alert-danger mb-2">
                                You have entered a value greator then your maximum amount for this unit.
                                You will not be able to send your units off to battle till you fix this.
                            </div>
                        : null
                    }
                    <label htmlFor={unit.name}>{unit.name} (Max: {unit.amount})</label>
                    <input 
                        type="number"
                        steps="1" 
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
                            </Card.Body>
                        </Accordion.Collapse>
                    </Card>
                );
            }
        });
    }

    render() {
        return(
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
                                    <dt><strong>Time To Move</strong>:</dt>
                                    <dd>0</dd>
                                    <dt><strong>Total Units To Send</strong>:</dt>
                                    <dd>0</dd>
                                </dl>
                            </div>
                        </>
                }
            </div>
        );
    }
}