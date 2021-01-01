import React          from 'react';
import {Modal}        from 'react-bootstrap';
import ReactDatatable from '@ashvin27/react-datatable';
import BuildingManagementModal from './building-management-modal';

export default class KingdomManagementModal extends React.Component {

    constructor(props) {
        super(props)

        this.columns = [
            {
                key: "name",
                text: "Name",
                sortable: true
            },
            {
                key: "level",
                text: "Level",
                sortable: true
            },
            {
                key: "current_durability",
                text: "Current Durability",
                sortable: true
            },
            {
                key: "current_defence",
                text: "Current Defence",
                sortable: true
            },
            {
                key: "wood_cost",
                text: "Wood Cost",
                sortable: true
            },
            {
                key: "clay_cost",
                text: "Clay Cost",
                sortable: true
            },
            {
                key: "stone_cost",
                text: "Stone Cost",
                sortable: true
            },
            {
                key: "iron_cost",
                text: "Iron Cost",
                sortable: true
            },
        ];

        this.config = {
            page_size: 5,
            length_menu: [5, 10, 25],
            show_filter: true,
            show_pagination: true,
            pagination: 'advance',
        }

        this.state = {
            openBuildingManagement: false,
            buildingToManage: null,
        }
    }


    adjust(color, amount) {
        return '#' + color.replace(/^#/, '').replace(/../g, color => ('0'+Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).substr(-2));
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
                console.log(building.morale_increase);
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

    getCurrentMorale() {
        return (this.props.kingdom.current_morale * 100).toFixed(2);
    }

    rowClickedHandler(event, data, rowIndex) {
        this.setState({
            openBuildingManagement: true,
            building: data,
        });
    }

    closeBuildingManagement() {
        this.setState({
            openBuildingManagement: false,
            building: null,
        });
    }

    render() {
        console.log(this.props.kingdom);

        return (
            <Modal
                show={this.props.show}
                onHide={this.props.close}
                dialogClassName="large-modal"
                aria-labelledby="kingdom-management-modal"
                backdrop="static"
            >
                <Modal.Header closeButton style={{backgroundColor: this.adjust(this.props.kingdom.color, 50)}}>
                    <Modal.Title id="kingdom-management-modal" style={{color: '#fff'}}>
                        Manage Your Kingdom
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className="row">
                        <div className="col-md-2">
                            <dl>
                                <dt><strong>Name</strong>:</dt>
                                <dd>{this.props.kingdom.name}</dd>
                            </dl>
                        </div>
                        <div className="col-md-2">
                            <dl>
                                <dt><strong>Population</strong>:</dt>
                                <dd>{this.props.kingdom.current_population} / {this.props.kingdom.max_population}</dd>
                            </dl>
                        </div>
                        <div className="col-md-2">
                            <dl>
                                <dt><strong>Morale</strong>:</dt>
                                <dd>{this.getCurrentMorale()}%</dd>
                            </dl>
                        </div>
                        <div className="col-md-2">
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
                                <dt><strong>Wood increase ph</strong>:</dt>
                                <dd>{this.getResourceIncrease('wood_increase')}</dd>
                            </dl> 
                        </div>
                        <div className="col-md-3">
                            <dl>
                                <dt><strong>Clay increase ph</strong>:</dt>
                                <dd>{this.getResourceIncrease('clay_increase')}</dd>
                            </dl> 
                        </div>
                        <div className="col-md-3">
                            <dl>
                                <dt><strong>Stone increase ph</strong>:</dt>
                                <dd>{this.getResourceIncrease('stone_increase')}</dd>
                            </dl> 
                        </div>
                        <div className="col-md-3">
                            <dl>
                            <dt><strong>Iron increase ph</strong>:</dt>
                                <dd>{this.getResourceIncrease('iron_increase')}</dd>
                            </dl> 
                        </div>
                    </div>
                    <hr />
                    <div className="row">
                        <div className="col-md-6">
                            <dl>
                                <dt><strong>Morale Increase Per Hour</strong>:</dt>
                                <dd>{this.getTotalMoraleIncreasePerHour()}%</dd>
                            </dl>
                        </div>
                        <div className="col-md-6">
                            <dl>
                                <dt><strong>Morale Decrease Per Hour</strong>:</dt>
                                <dd>{this.getTotalMoraleDecreasePerHour()}%</dd>
                            </dl>
                        </div>
                    </div>
                    <hr />
                    <div className="row">
                        <div className="col-md-12">
                            <ReactDatatable
                                config={this.config}
                                records={this.props.kingdom.buildings}
                                columns={this.columns}
                                onRowClicked={this.rowClickedHandler.bind(this)}        
                            />
                        </div>
                    </div>

                    
                    { this.state.openBuildingManagement ?
                    <BuildingManagementModal
                        close={this.closeBuildingManagement.bind(this)}
                        show={this.state.openBuildingManagement}
                        building={this.state.building}
                        kingdom={this.props.kingdom}
                    /> : null }

                </Modal.Body>
            </Modal>
        );
    }
}