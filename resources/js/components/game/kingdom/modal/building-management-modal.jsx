import React, {Fragment} from 'react';
import {Modal, ModalDialog} from 'react-bootstrap';
import Draggable from 'react-draggable';
import UpgradeSection from './partials/building-management/upgrade-section';
import BuildingCostSection from './partials/building-management/building-cost-section';

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

    this.state = {
      disabledButtons: false,
      loading: false,
    }
  }

  canUpgrade() {
    const kingdom = this.props.kingdom;
    const building = this.props.building;

    if (building.level >= building.max_level) {
      return false
    }

    if (building.wood_cost > kingdom.current_wood) {
      return false;
    }

    if (building.clay_cost > kingdom.current_clay) {
      return false;
    }

    if (building.stone_cost > kingdom.current_stone) {
      return false;
    }

    if (building.iron_cost > kingdom.current_iron) {
      return false;
    }

    if (building.population_required > kingdom.current_population) {
      return false;
    }

    return true;
  }

  canRebuild() {
    const kingdom = this.props.kingdom;
    const building = this.props.building;

    if ((building.level * building.base_wood_cost) > kingdom.current_wood) {
      return false;
    }

    if ((building.level * building.base_clay_cost) > kingdom.current_clay) {
      return false;
    }

    if ((building.level * building.base_stone_cost) > kingdom.current_stone) {
      return false;
    }

    if ((building.level * building.base_iron_cost) > kingdom.current_iron) {
      return false;
    }

    if ((building.level * building.base_population) > kingdom.current_population) {
      return false;
    }

    return true;
  }

  buildingNeedsToBeRebuilt() {
    return this.props.building.current_durability === 0;
  }

  isCurrentlyInQueue() {
    return _.isEmpty(this.props.queue.filter((q) => q.building_id === this.props.building.id));
  }

  upgradeBuilding() {
    this.setState({
      disabledButtons: true,
      loading: true,
    }, () => {
      axios.post('/api/kingdoms/' + this.props.characterId + '/upgrade-building/' + this.props.building.id)
        .then((result) => {
          this.props.showBuildingSuccess(this.props.building.name + ' is in queue (being upgraded). You can see this in the Building Queue tab.');
          this.props.close();
        })
        .catch((err) => {
          this.props.close();

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

  rebuildBuilding() {
    this.setState({
      disabledButtons: true,
      loading: true,
    }, () => {
      axios.post('/api/kingdoms/' + this.props.characterId + '/rebuild-building/' + this.props.building.id)
        .then((result) => {
          this.props.showBuildingSuccess(this.props.building.name + ' is in queue (being rebuilt). You can see this in the Building Queue tab.');
          this.props.close();
        })
        .catch((err) => {
          if (err.hasOwnProperty('response')) {
            const response = err.response;

            if (response.status === 401) {
              return location.reload();
            }

            if (response.status === 429) {
              return this.props.openTimeOutModal();
            }
          }
        });
    });
  }

  subTitle() {
    if (this.props.building.is_farm) {
      return (
        <span className="text-muted" style={{fontSize: '16px'}}>(increases population by +100 per level)</span>
      );
    }

    if (this.props.building.is_resource_building) {
      return (
        <span className="text-muted" style={{fontSize: '16px'}}>(increases resource by specified amount)</span>
      );
    }
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
            {this.props.building.name} {this.subTitle()}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p>{this.props.building.description}</p>
          <hr/>
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
          <hr/>
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
            <p className="mt-3 ml-2 text-muted"><small><sup>*</sup> Kingdom morale only decreases if this building's
              durability is 0.</small></p>
          </div>
          <hr/>
          <div className="row">
            {this.props.building.level >= this.props.building.max_level ?
              <div className="col-md-12">
                <div className="alert alert-success mt-2">
                  This building is already max level and cannot upgrade any further.
                </div>
              </div>
              : <div className="col-md-6">
                  <h5 className="mt-1">Gain Upon Upgrading</h5>
                  <hr/>
                  <UpgradeSection building={this.props.building}/>
                </div>
            }

            {!this.isCurrentlyInQueue() ?
              <div className="col-md-6">
                <div className="alert alert-warning mb-2 mt-2">
                  Cannot upgrade building. Currently in queue. Please wait till it's finished.
                </div>
              </div>
              : !this.canUpgrade() && !(this.props.building.level >= this.props.building.max_level) ?
                <div className="col-md-6">
                  <div className="alert alert-warning mb-2 mt-2">
                    You don't seem to have the resources to upgrade this building. You can move this modal
                    by clicking and dragging on the title, to compare the required resources with what you currently have.
                  </div>
                  <BuildingCostSection
                    building={this.props.building}
                    canUpgrade={this.canUpgrade() && this.isCurrentlyInQueue()}
                  />
                </div>
                : !this.buildingNeedsToBeRebuilt() && !(this.props.building.level >= this.props.building.max_level) ?
                  <div className="col-md-6">
                    <hr/>
                    <h5 className="mt-1">Cost to upgrade</h5>
                    <hr/>
                    <div className="mt-2 mb-2 alert alert-info">
                      You can click and drag the title to move the modal and make sure you have the resources before
                      attempting to upgrade.
                    </div>
                    <BuildingCostSection
                      building={this.props.building}
                      canUpgrade={this.canUpgrade() && this.isCurrentlyInQueue()}
                    />
                  </div>
                : this.buildingNeedsToBeRebuilt() ?
                    <Fragment>
                      <div className="col-md-6">
                        <div className="alert alert-info mt-2">
                          Rebuilding the building will require the amount of resources to upgrade to the current level.
                          You can see this in the Cost section above.
                        </div>
                      </div>
                      <div className="col-md-6">
                        <h4>Cost</h4>
                        <hr />
                        <BuildingCostSection
                          building={this.props.building}
                          canUpgrade={this.canUpgrade() && this.isCurrentlyInQueue()}
                        />
                      </div>
                    </Fragment>
                : null
            }
          </div>
          {
            this.state.loading ?
              <div className="progress loading-progress kingdom-loading " style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
              : null
          }
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-danger" onClick={this.props.close}>Cancel</button>
          {
            this.buildingNeedsToBeRebuilt() ?
              <button className="btn btn-primary"
                      disabled={!this.canRebuild() || !this.isCurrentlyInQueue() || this.state.disabledButtons}
                      onClick={this.rebuildBuilding.bind(this)}>Rebuild</button>
              :
              <button className="btn btn-success"
                      disabled={!this.canUpgrade() || !this.isCurrentlyInQueue() || this.state.disabledButtons}
                      onClick={this.upgradeBuilding.bind(this)}
              >
                Upgrade
              </button>
          }

        </Modal.Footer>
      </Modal>
    );
  }
}
