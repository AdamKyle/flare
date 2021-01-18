import React from 'react';

export default class KingdomInfo extends React.Component {

    constructor(props) {
        super(props);
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

        if (this.props.kingdom.current_morale >= 1.0) {
            return currentMoraleIncrease;
        }

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

        if (this.props.kingdom.current_morale === 0) {
            return currentMoraleDecrease;
        }

        const buildings = this.props.kingdom.buildings;

        buildings.forEach((building) => {
            if (building.current_durability === 0) {
                currentMoraleDecrease += building.morale_decrease;
            }
        });

        return (currentMoraleDecrease * 100).toFixed(2);
    }

    render() {
        return (
            <>
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
                            <dt><strong>Treasury</strong>:</dt>
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
                            <dd>{this.props.kingdom.current_stone} / {this.props.kingdom.current_stone}</dd>
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
            </>
        )
    }
}