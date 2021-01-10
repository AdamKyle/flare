import React     from 'react';
import {Modal, ModalDialog}   from 'react-bootstrap';
import Draggable from 'react-draggable';

class DraggableModalDialog extends React.Component {
    render() {
        return (
            <Draggable handle=".modal-title">
                <div>
                    <ModalDialog {...this.props} />
                </div> 
            </Draggable>
        );
    }
}

export default class BuildingManagementModal extends React.Component {

    constructor(props) {
        super(props)

        this.state = {}
    }

    canUpgrade() {
        const kingdom  = this.props.kingdom;
        const building = this.props.building;

        if (building.current_durability === 0) {
            return false;
        }

        if (building.wood_cost > kingdom.current_wood) {
            return false;
        }

        if (building.clay_cost > kingdom.current_wood) {
            return false;
        }

        if (building.stone_cost > kingdom.current_wood) {
            return false;
        }

        if (building.iron_cost > kingdom.current_wood) {
            return false;
        }

        if (building.population_required > kingdom.current_population) {
            return false;
        }

        return true;
    }

    getIncrease(type) {
        const building = this.props.building;
        
        if (building.hasOwnProperty('future_' + type + '_increase')) {
            return building['future_' + type + '_increase'];
        }

        return 0;
    }

    isCurrentlyInQueue() {
        return _.isEmpty(this.props.queue.filter((q) => q.building_id === this.props.building.id));
    }

    upgradeBuilding() {
        axios.post('/api/kingdoms/'+this.props.characterId+'/upgrade-building/' + this.props.building.id)
             .then((result) => {
                this.props.updateKingdomData(result.data);
                this.props.close();
             })
             .catch((error) => {
                console.error(error);
             });
    }

    render() {
        return (
            <Modal
                dialogAs={DraggableModalDialog}
                show={this.props.show}
                onHide={this.props.close}
                aria-labelledby="building-management-modal"
                dialogClassName="building-management"
                centered
            >
                <Modal.Header closeButton>
                    <Modal.Title id="building-management-modal">
                        {this.props.building.name}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <p>{this.props.building.description}</p>
                    <hr />
                    <div className="row">
                        <div className="col-md-4">
                            <dl>
                                <dt><strong>Level</strong>:</dt>
                                <dd>{this.props.building.level}</dd>
                            </dl>
                        </div>
                        <div className="col-md-4">
                            <dl>
                                <dt><strong>Durability</strong>:</dt>
                                <dd>{this.props.building.current_durability} / {this.props.building.max_durability}</dd>
                            </dl>
                        </div>
                        <div className="col-md-4">
                            <dl>
                                <dt><strong>Defence</strong>:</dt>
                                <dd>{this.props.building.current_defence} / {this.props.building.max_defence}</dd>
                            </dl>
                        </div>
                    </div>
                    <hr />
                    <div className="row">
                        <div className="col-md-6">
                            <dl>
                                <dt><strong>Morale Increase/h</strong>:</dt>
                                <dd>{(this.props.building.morale_increase * 100).toFixed(2)}%</dd>
                            </dl>
                        </div>
                        <div className="col-md-6">
                            <dl>
                                <dt><strong>Morale Decrease/h</strong><sup>*</sup>:</dt>
                                <dd>{(this.props.building.morale_decrease * 100).toFixed(2)}%</dd>
                            </dl>
                        </div>
                        <p className="mt-3 ml-2 text-muted"><small><sup>*</sup> Kingdom morale only decreases if this building's durability is 0.</small></p>
                    </div>
                    <hr />
                    <div className="row">
                        <div className="col-md-6">
                            <dl>
                                <dt><strong>Wood Cost</strong>:</dt>
                                <dd>{this.props.building.wood_cost}</dd>
                                <dt><strong>Clay Cost</strong>:</dt>
                                <dd>{this.props.building.clay_cost}</dd>
                                <dt><strong>Stone Cost</strong>:</dt>
                                <dd>{this.props.building.stone_cost}</dd>
                                <dt><strong>Iron Cost</strong>:</dt>
                                <dd>{this.props.building.iron_cost}</dd>
                                <dt><strong>Population Cost</strong>:</dt>
                                <dd>{this.props.building.population_required}</dd>
                            </dl>
                        </div>
                        <div className="col-md-6">
                            <dl>
                                <dt><strong>Can Upgrade</strong>:</dt>
                                <dd>{this.canUpgrade() && this.isCurrentlyInQueue() ? 'Yes' : 'No'}</dd>
                                <dt><strong>Needs Repair</strong>:</dt>
                                <dd>{this.props.building.current_durability === 0 ? 'Yes' : 'No'}</dd>
                                <dt><strong>Upgrade Time</strong>:</dt>
                                <dd>{this.props.building.time_increase} Minutes</dd>
                            </dl>
                        </div>
                    </div>
                    <hr />
                    <h5 className="mt-1">Gain Upon Upgrading</h5>
                    <hr />
                    { !this.isCurrentlyInQueue() ?
                        <div className="alert alert-warning mb-2 mt-2">
                            Cannot upgrade building. Currently in queue. Please wait till it's finished.
                        </div>
                    : !this.canUpgrade() ? 
                        <div className="alert alert-warning mb-2 mt-2">
                            You don't seem to have the resources to upgrade this building. You can move this modal
                            by clicking and dragging on the title, to compare the required resources with what you currently have.
                        </div>
                    :
                        <div className="row mt-2">
                            <div className="col-md-6">
                                <dl>
                                    <dt><strong>Wood Gain/hr</strong>:</dt>
                                    <dd className="text-success">{this.getIncrease('wood')}</dd>
                                    <dt><strong>Clay Gain/hr</strong>:</dt>
                                    <dd className="text-success">{this.getIncrease('clay')}</dd>
                                    <dt><strong>Stone Gain/hr</strong>:</dt>
                                    <dd className="text-success">{this.getIncrease('stone')}</dd>
                                    <dt><strong>Iron Gain/hr</strong>:</dt>
                                    <dd className="text-success">{this.getIncrease('iron')}</dd>
                                    <dt><strong>Population Gain/hr</strong>:</dt>
                                    <dd className="text-success">{this.getIncrease('population')}</dd>
                                </dl>
                            </div>
                            <div className="col-md-6">
                                <dl>
                                    <dt><strong>Durability Becomes</strong>:</dt>
                                    <dd className="text-success">{this.getIncrease('durability')}</dd>
                                    <dt><strong>Defence Becomes</strong>:</dt>
                                    <dd className="text-success">{this.getIncrease('defence')}</dd>
                                </dl>
                            </div>
                        </div>
                    }
                </Modal.Body>
                <Modal.Footer>
                    <button className="btn btn-danger" onClick={this.props.close}>Cancel</button>
                    <button className="btn btn-success" disabled={!this.canUpgrade() || !this.isCurrentlyInQueue()} onClick={this.upgradeBuilding.bind(this)}>Upgrade</button>
                </Modal.Footer>
            </Modal>
        );
    }
}