import React, {Fragment} from 'react';
import {Modal, ModalDialog, Tabs, Tab, Col, Row} from 'react-bootstrap';
import Draggable from 'react-draggable';
import UpgradeSection from './partials/building-management/upgrade-section';
import BuildingCostSection from './partials/building-management/building-cost-section';
import AlertError from "../../components/base/alert-error";

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
      costToUpgrade: this.props.building.upgrade_cost,
      level: 0,
      populationRequired: 0,
      timeNeeded: 0,
      hasGold: true,
      activeTab: 'regular-upgrade',
    }
  }

  componentDidMount() {
    console.log('hello?');
    if (!this.canUpgrade()) {
      this.setState({
        activeTab: 'gold-upgrade'
      });
    }
  }

  canUpgrade() {
    const kingdom                   = this.props.kingdom;
    const building                  = this.props.building;
    const buildingCostReduction     = this.props.kingdom.building_cost_reduction;
    const ironCostReduction         = this.props.kingdom.iron_cost_reduction;
    const populationCostReduction   = this.props.kingdom.population_cost_reduction;

    let buildingWoodCost = building.wood_cost;
    buildingWoodCost    -= Math.floor(buildingWoodCost * buildingCostReduction);

    if (buildingWoodCost > kingdom.current_wood) {
      return false;
    }

    let buildingClayCost = building.clay_cost;
    buildingClayCost    -= Math.floor(buildingClayCost * buildingCostReduction);

    if (buildingClayCost > kingdom.current_clay) {
      return false;
    }

    let buildingStoneCost = building.stone_cost;
    buildingStoneCost    -= Math.floor(buildingStoneCost * buildingCostReduction);

    if (buildingStoneCost > kingdom.current_stone) {
      return false;
    }

    let buildingIronCost = building.iron_cost;
    buildingIronCost    -= Math.floor(buildingIronCost * (buildingCostReduction + ironCostReduction));

    if (buildingIronCost > kingdom.current_iron) {
      return false;
    }

    let buildingPopulationRequired = building.population_required;
    buildingPopulationRequired    -= Math.floor(buildingPopulationRequired * (buildingCostReduction + populationCostReduction));

    if (buildingPopulationRequired > kingdom.current_population) {
      return false;
    }

    return true;
  }

  canRebuild() {
    const kingdom                   = this.props.kingdom;
    const building                  = this.props.building;
    const buildingCostReduction     = this.props.kingdom.building_cost_reduction;
    const ironCostReduction         = this.props.kingdom.iron_cost_reduction;
    const populationCostReduction   = this.props.kingdom.population_cost_reduction;

    let buildingWoodCost = (building.level * building.base_wood_cost);
    buildingWoodCost    -= Math.floor(buildingWoodCost * buildingCostReduction)

    if (buildingWoodCost > kingdom.current_wood) {
      return false;
    }

    let buildingClayCost = (building.level * building.base_clay_cost);
    buildingClayCost    -= Math.floor(buildingClayCost * buildingCostReduction)

    if (buildingClayCost > kingdom.current_clay) {
      return false;
    }

    let buildingStoneCost = (building.level * building.base_stone_cost);
    buildingStoneCost    -= Math.floor(buildingStoneCost * buildingCostReduction)

    if (buildingStoneCost > kingdom.current_stone) {
      return false;
    }

    let buildingIronCost = (building.level * building.base_iron_cost);
    buildingIronCost    -= Math.floor(buildingIronCost - buildingIronCost * (buildingCostReduction + ironCostReduction))

    if (buildingIronCost > kingdom.current_iron) {
      return false;
    }

    let buildingPopulationRequired = (building.level * building.base_population);
    buildingPopulationRequired    -= Math.floor(buildingPopulationRequired * (buildingCostReduction + populationCostReduction))

    if (buildingPopulationRequired > kingdom.current_population) {
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
      axios.post('/api/kingdoms/' + this.props.characterId + '/upgrade-building/' + this.props.building.id, {
        cost_to_upgrade: this.state.costToUpgrade,
        how_many_levels: this.state.level,
        pop_required: this.state.populationRequired,
        time: this.state.timeNeeded,
        paying_with_gold: this.state.level > 0,
      })
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

  changeLevel(event) {
    let value      = parseInt(event.target.value) || 0;
    const maxLevel = this.props.building.max_level - this.props.building.level;

    if (value > maxLevel) {
      value = maxLevel;
    }

    this.setState({
      level_increase_to: value,
    }, () => {
      this.processLevel(value);
    });
  }

  getNewLevel() {

    if (this.state.level === 0) {
      return 0;
    }

    const value = this.state.level + this.props.building.level;

    if (value > this.props.building.max_level) {
      return this.props.building.max_level;
    }

    return value;
  }

  processLevel(level) {
    let levelForGoldCost = level - this.props.building.level;

    if (levelForGoldCost <= 0) {
      levelForGoldCost = level;
    }

    let goldCost        = levelForGoldCost * this.props.building.upgrade_cost;
    const characterGold = parseInt(this.props.characterGold.replace(/,/g, ''));
    let hasGold         = characterGold >= goldCost;

    let time = 0;

    for (let i = 1; i <= level; i++) {
      let level   = this.props.building.level + 1;
      let newTime = level + this.props.building.raw_time_to_build;

      newTime = newTime + newTime * this.props.building.raw_time_increase;

      newTime = newTime - (newTime * this.props.kingdom.building_time_reduction)

      if (newTime < 1) {
        newTime = 1;
      }

      time = time + newTime;
    }

    let populationRequired = (level + 1) * this.props.building.raw_required_population;

    goldCost           -= Math.ceil(goldCost * this.props.kingdom.building_cost_reduction);
    populationRequired -= (Math.ceil(populationRequired * (this.props.kingdom.population_cost_reduction + this.props.kingdom.building_cost_reduction)));

    this.setState({
      disabledButtons: !hasGold,
      costToUpgrade: goldCost,
      hasGold: hasGold,
      level: level,
      populationRequired: populationRequired,
      timeNeeded: Math.floor(time),
    })
  }

  formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  calculateHours(number) {
    return ((1/60) * number).toFixed(2);
  }

  populationCost() {
    const amountOfPopLeft = this.state.populationRequired - this.props.kingdom.current_population;
    const price           = amountOfPopLeft * 10;

    return this.formatNumber(price);
  }

  setActiveTab(key) {
    console.log(key);
    this.setState({
      activeTab: key,
    })
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
          {
            this.props.building.is_locked ?
              <AlertError icon={"fas fa-exclamation-circle"} title={'This building is locked'}>
                <p>
                  This building is locked behind the {this.props.building.passive_skill_name} Passive skill.
                  You can find this skill on your character sheet under Passive Skills section. Once you train this skill
                  you can then begin building and upgrading this building.
                </p>
              </AlertError>
            : null
          }
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
          <Tabs activeKey={this.state.activeTab} onSelect={this.setActiveTab.bind(this)} id="building-upgrade">
            <Tab eventKey="regular-upgrade" title="Regular Upgrade" disabled={this.props.building.is_locked || !this.canUpgrade()}>
              <div className="row mt-4">
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
                  : !this.buildingNeedsToBeRebuilt() && !this.canUpgrade() && !(this.props.building.level >= this.props.building.max_level) ?
                    <div className="col-md-6">
                      <div className="alert alert-warning mb-2 mt-2">
                        You don't seem to have the resources to upgrade this building. You can move this modal
                        by clicking and dragging on the title, to compare the required resources with what you currently have.
                      </div>
                      <BuildingCostSection
                        building={this.props.building}
                        kingdom={this.props.kingdom}
                        canUpgrade={this.canUpgrade() && this.isCurrentlyInQueue()}
                      />
                    </div>
                    : !this.buildingNeedsToBeRebuilt() && !(this.props.building.level >= this.props.building.max_level) ?
                      <div className="col-md-6">
                        <h5 className="mt-1">Cost to upgrade</h5>
                        <hr/>
                        <div className="mt-2 mb-2 alert alert-info">
                          You can click and drag the title to move the modal and make sure you have the resources before
                          attempting to upgrade.
                        </div>
                        <BuildingCostSection
                          building={this.props.building}
                          kingdom={this.props.kingdom}
                          canUpgrade={this.canUpgrade() && this.isCurrentlyInQueue()}
                        />
                      </div>
                    : this.buildingNeedsToBeRebuilt() ?
                        <Fragment>
                          <div className="col-md-6">
                            <div className="alert alert-info mt-2">
                              Rebuilding the building will require the amount of resources to upgrade to the current level.
                              You can see this in the Cost section below.
                            </div>
                            {!this.canUpgrade() ?
                              <div className="alert alert-warning mb-2 mt-2">
                                You don't seem to have the resources to upgrade this building. You can move this modal
                                by clicking and dragging on the title, to compare the required resources with what you currently have.
                              </div>
                              : null
                            }

                            <h5 className="mt-1">Cost</h5>
                            <hr />
                            <BuildingCostSection
                              building={this.props.building}
                              kingdom={this.props.kingdom}
                              canUpgrade={this.canUpgrade() && this.isCurrentlyInQueue()}
                            />
                          </div>
                        </Fragment>
                    : null
                }
              </div>
            </Tab>
            <Tab eventKey="gold-upgrade" title="Gold Upgrade" disabled={this.buildingNeedsToBeRebuilt() || !this.isCurrentlyInQueue() || (this.props.building.level >= this.props.building.max_level) || this.props.building.is_locked}>
              <div className="mt-4">
                <Row>
                  <Col lg={12} xl={6}>
                    <dl>
                      <dt>Max Level</dt>
                      <dd>{this.formatNumber(this.props.building.max_level)}</dd>
                      <dt>Population Required</dt>
                      <dd>{this.formatNumber(this.state.populationRequired)} (-{((this.props.kingdom.population_cost_reduction + this.props.kingdom.building_cost_reduction) * 100).toFixed(2)}%)</dd>
                      <dt>Cost per Level</dt>
                      <dd>{this.formatNumber(this.props.building.upgrade_cost)}</dd>
                      <dt>Time Needed (Minutes)</dt>
                      <dd>{this.formatNumber(this.state.timeNeeded)}, (~{this.calculateHours(this.state.timeNeeded)} hrs.) (-{(this.props.kingdom.building_time_reduction * 100).toFixed(2)}%)</dd>
                      <dt>Total Gold</dt>
                      <dd>{this.state.level > 0 ? this.formatNumber(this.state.costToUpgrade * this.state.level) : 0} (-{(this.props.kingdom.building_cost_reduction * 100).toFixed(2)}%)</dd>
                      <dt>Will Upgrade To Level:</dt>
                      <dd>{this.getNewLevel()}</dd>
                    </dl>
                    <p className="mt-3">The negative percentage values come from you training: <a href="/information/passive-skills">Passive Skills</a> which help to reduce
                      things like resources needed, population needed and by training the Kingmanship skill to reduce time needed.</p>
                  </Col>
                  <Col lg={12} xl={6}>
                    <div className="tw-overflow-x-auto tw-max-h-52">
                      <p>
                        Upgrading with gold will let you choose a number of levels to upgrade from 0 to the max building level. If the building already has levels,
                        that will be taken into account for the cost and time calculation.
                      </p>
                      <p>
                        If you do not have the population, you can still purchase the building upgrade and we will only purchase the people needed on top of the cost
                        of the building upgrade. For example, if you have 100 people and the upgrade costs 500 people, we will only purchase 400 people (10 x 400 = 4000 gold)
                        on top of the cost of the upgrade.
                      </p>
                      <p>
                        New players are discourage from purchasing upgrades in the beginning when gold is scare for them.
                      </p>
                    </div>
                  </Col>
                </Row>

                {
                  this.props.kingdom.current_population < this.state.populationRequired && this.state.populationRequired !== 0 ?
                    <div className="alert alert-warning mt-2 mt-3">
                      You're population requirement is greater then amount of population you have. You can continue, but
                      it will cost an additional: {this.populationCost()} Gold on top of the cost to upgrade. Canceling the upgrade will <strong>
                      not give you the gold for the population or the population back.
                    </strong>
                    </div>
                  : null
                }

                {
                  !this.state.hasGold ?
                    <div className="alert alert-danger mt-2 mt-3">
                      You do not have the gold to purchase the upgrade.
                    </div>
                  : null
                }
                <div className="form-group mt-3">
                  <label htmlFor="gold-amount">How many levels?</label>
                  <input
                    className="form-control"
                    name="gold-amount"
                    type="number"
                    min={0}
                    max={this.props.building.max_level}
                    value={this.state.level}
                    onChange={this.changeLevel.bind(this)}
                  />
                </div>
              </div>
            </Tab>
          </Tabs>
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
                      disabled={!this.canRebuild() || !this.isCurrentlyInQueue() || this.state.disabledButtons || this.props.building.is_locked}
                      onClick={this.rebuildBuilding.bind(this)}>Rebuild</button>
              :
              <button className="btn btn-success"
                      disabled={(!this.canUpgrade() && !(this.state.level > 0)) || !this.isCurrentlyInQueue() || this.state.disabledButtons || this.props.building.is_locked}
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
