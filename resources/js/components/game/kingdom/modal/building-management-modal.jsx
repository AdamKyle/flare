import React from 'react';
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

    this.state = {}
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
  }

  rebuildBuilding() {
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
          {/*<BuildingCostSection*/}
          {/*  building={this.props.building}*/}
          {/*  canUpgrade={this.canUpgrade() && this.isCurrentlyInQueue()}*/}
          {/*/>*/}

          { this.props.building.level >= this.props.building.max_level ?
            <div className="alert alert-success mt-5">
              This building is already max level and cannot upgrade any further.
            </div>
          : <BuildingCostSection
              building={this.props.building}
              canUpgrade={this.canUpgrade() && this.isCurrentlyInQueue()}
            />}

          {!this.isCurrentlyInQueue() ?
            <div className="alert alert-warning mb-2 mt-2">
              Cannot upgrade building. Currently in queue. Please wait till it's finished.
            </div>
            : !this.canUpgrade() && !(this.props.building.level >= this.props.building.max_level) ?
              <div className="alert alert-warning mb-2 mt-2">
                You don't seem to have the resources to upgrade this building. You can move this modal
                by clicking and dragging on the title, to compare the required resources with what you currently have.
              </div>
              : !this.buildingNeedsToBeRebuilt() && !(this.props.building.level >= this.props.building.max_level) ?
                <>
                  <hr/>
                  <h5 className="mt-1">Gain Upon Upgrading</h5>
                  <hr/>
                  <UpgradeSection building={this.props.building}/>
                </>
                  : !(this.props.building.level >= this.props.building.max_level) ?
                <div className="alert alert-info mt-5">
                  Rebuilding the building will require the amount of resources to upgrade to the current level.
                  You can see this in the Cost section above.
                </div> : null
          }
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-danger" onClick={this.props.close}>Cancel</button>
          {
            this.buildingNeedsToBeRebuilt() ?
              <button className="btn btn-primary"
                      disabled={!this.canRebuild() || !this.isCurrentlyInQueue()}
                      onClick={this.rebuildBuilding.bind(this)}>Rebuild</button>
              :
              <button className="btn btn-success"
                      disabled={!this.canUpgrade() || !this.isCurrentlyInQueue()}
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
