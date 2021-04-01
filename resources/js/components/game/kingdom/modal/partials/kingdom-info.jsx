import React from 'react';
import Embezzel from './Embezzel';
import {Alert} from 'react-bootstrap';

export default class KingdomInfo extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            showEmbezzel: false,
            showSuccess: false,
            successMessage: null,
        }
    }

    getCurrentMorale() {
        return (this.props.kingdom.current_morale * 100).toFixed(2);
    }

    getTreasury() {
        if (this.props.kingdom.treasury === null) {
            return 0;
        }
        
        return this.props.kingdom.treasury.toLocaleString('en-US', {maximumFractionDigits:0});
    }

    getResourceIncrease(type) {
        const building = this.props.kingdom.buildings.filter((b) => b[type] !== 0);
        
        if (_.isEmpty(building)) {
            return 0;
        }

        return building[0][type];
    }

    getTotalMoraleIncreasePerHour() {
        let currentMoraleIncrease = 0;

        const buildings = this.props.kingdom.buildings;

        buildings.forEach((building) => {
            if (building.current_durability !== 0) {
                currentMoraleIncrease += building.morale_increase;
            }
        });

        return (currentMoraleIncrease * 100).toFixed(2);
    }

    getTotalMoraleDecreasePerHour() {
        let currentMoraleDecrease = 0;

        const buildings = this.props.kingdom.buildings;

        buildings.forEach((building) => {
            if (building.current_durability === 0) {
                currentMoraleDecrease += building.morale_decrease;
            }
        });

        return (currentMoraleDecrease * 100).toFixed(2);
    }

    showEmbezzel() {
        this.setState({
            showEmbezzel: true,
        });
    }

    closeEmbezzel() {
        this.setState({
            showEmbezzel: false,
        });
    }

    embezzeledSuccess(amount) {
        this.setState({
            showSuccess: true,
            successMessage: 'Embezzeled ' + amount + ' gold from kingdom. The kingdoms morale has dropped by 15%.',
        });
    }

    closeSuccess() {
        this.setState({
            showSuccess: false,
            successMessage: null
        })
    }

    render() {
        return (
            <>
                {
                    this.state.showSuccess ?
                        <div className="mb-2 mt-2">
                            <Alert variant="success" onClose={this.closeSuccess.bind(this)} dismissible>
                                {this.state.successMessage}
                            </Alert>
                        </div>
                    : null
                }
                <div className="row mt-3">
                    <div className="col-md-3">
                        <dl>
                            <dt><strong>Population</strong>:</dt>
                            <dd>{this.props.kingdom.current_population} / {this.props.kingdom.max_population}</dd>
                        </dl>
                    </div>
                    <div className="col-md-3">
                        <dl>
                            <dt><strong>Morale</strong>:</dt>
                            <dd>{this.getCurrentMorale()}%</dd>
                        </dl>
                    </div>
                    <div className="col-md-3">
                        <dl>
                            <dt><strong><button className="btn btn-link treasury-btn" onClick={this.showEmbezzel.bind(this)}>Treasury:</button></strong></dt>
                            <dd>{this.getTreasury()}</dd>
                        </dl>
                    </div>
                    <div className="col-md-3">
                        <dl>
                            <dt><strong>Location (X/Y)</strong>:</dt>
                            <dd>{this.props.kingdom.x_position} / {this.props.kingdom.y_position}</dd>
                        </dl>
                    </div>
                </div>
                <hr />
                <div className="row">
                    <div className="col-md-3">
                        <dl>
                            <dt><strong>Wood</strong>:</dt>
                            <dd>{this.props.kingdom.current_wood} / {this.props.kingdom.max_wood}</dd>
                        </dl>
                    </div>
                    <div className="col-md-3">
                        <dl>
                            <dt><strong>Clay</strong>:</dt>
                            <dd>{this.props.kingdom.current_clay} / {this.props.kingdom.max_clay}</dd>
                        </dl>
                    </div>
                    <div className="col-md-3">
                        <dl>
                            <dt><strong>Stone</strong>:</dt>
                            <dd>{this.props.kingdom.current_stone} / {this.props.kingdom.max_stone}</dd>
                        </dl>
                    </div>
                    <div className="col-md-3">
                        <dl>
                            <dt><strong>Iron</strong>:</dt>
                            <dd>{this.props.kingdom.current_iron} / {this.props.kingdom.max_iron}</dd>
                        </dl>
                    </div>
                </div>
                <hr />
                <div className="row">
                    <div className="col-md-3">
                        <dl>
                            <dt><strong>Wood Increase/hr</strong>:</dt>
                            <dd>{this.getResourceIncrease('wood_increase')}</dd>
                        </dl> 
                    </div>
                    <div className="col-md-3">
                        <dl>
                            <dt><strong>Clay Increase/hr</strong>:</dt>
                            <dd>{this.getResourceIncrease('clay_increase')}</dd>
                        </dl> 
                    </div>
                    <div className="col-md-3">
                        <dl>
                            <dt><strong>Stone Increase/hr</strong>:</dt>
                            <dd>{this.getResourceIncrease('stone_increase')}</dd>
                        </dl> 
                    </div>
                    <div className="col-md-3">
                        <dl>
                        <dt><strong>Iron Increase/hr</strong>:</dt>
                            <dd>{this.getResourceIncrease('iron_increase')}</dd>
                        </dl> 
                    </div>
                </div>
                <hr />
                <div style={{backgroundColor: 'rgb(174 212 234)', padding: '20px'}}>
                    <div className="row">
                        <div className="col-md-4">
                            <dl>
                                <dt><strong>Morale Increase/hr</strong>:</dt>
                                <dd>{this.getTotalMoraleIncreasePerHour()}%</dd>
                            </dl>
                        </div>
                        <div className="col-md-4">
                            <dl>
                                <dt><strong>Morale Decrease/hr</strong>:</dt>
                                <dd>{this.getTotalMoraleDecreasePerHour()}%</dd>
                            </dl>
                        </div>
                        <div className="col-md-4">
                            <dl>
                            <dt><strong>Population Increase/hr</strong>:</dt>
                                <dd>{this.getResourceIncrease('population_increase')}</dd>
                            </dl> 
                        </div>
                    </div>
                </div>
                <Embezzel 
                    show={this.state.showEmbezzel}
                    close={this.closeEmbezzel.bind(this)}
                    morale={this.props.kingdom.current_morale}
                    treasury={this.props.kingdom.treasury}
                    kingdomId={this.props.kingdom.id}
                    embezzeledSuccess={this.embezzeledSuccess.bind(this)}
                />
            </>
        )
    }
}