import React from 'react';
import {Alert, Modal, Tab, Tabs} from 'react-bootstrap';
import BuildingManagementModal from './building-management-modal';
import QueueModal from './queue-modal';
import KingdomInfo from './partials/kingdom-info';
import KingdomBuildings from './partials/kingdom-buildings';
import KingdomBuildingQueue from './partials/kingdom-building-queue.jsx';
import UnitBuildingQueue from './partials/unit-recruitment-queue.jsx';
import KingdomUnits from './partials/kingdom-units';
import RecruitUnit from './recruit-unit';
import LoadingModal from '../../components/loading/loading-modal';
import KingdomRenameModal from './kingdom-raname-modal';
import AlertInfo from "../../components/base/alert-info";

export default class KingdomManagementModal extends React.Component {

  constructor(props) {
    super(props)

    this.state = {
      openBuildingManagement: false,
      buildingToManage: null,
      openQueueData: false,
      queue: null,
      unitData: null,
      openUnitModal: false,
      kingdom: null,
      queueType: null,
      isLoading: true,
      buildingUpgradeSuccess: '',
      showBuildingUpgradeSuccess: false,
      unitRecruitmentSuccess: '',
      showUnitRecruitmentSuccess: false,
      openKingdomEditNameModal: false,
    }

    this.updateKingdom = Echo.private('update-kingdom-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/kingdoms/' + this.props.characterId + '/' + this.props.kingdomId).then((result) => {
      this.setState({
        kingdom: result.data,
        isLoading: false,
      })
    }).catch((err) => {
      this.props.close();

      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          this.props.openTimeOutModal()
        }
      }
    });

    this.updateKingdom.listen('Game.Kingdoms.Events.UpdateKingdom', (event) => {
      if (this.state.kingdom.id === event.kingdom.id) {
        this.setState({
          kingdom: event.kingdom
        });
      }
    });
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

  queueData(event, data, rowIndex) {
    this.setState({
      openQueueData: true,
      queue: data,
      queueType: 'building',
    });
  }

  unitQueueData(event, data, rowIndex) {
    this.setState({
      openQueueData: true,
      queue: data,
      queueType: 'unit',
    })
  }

  recruitUnit(event, data, rowIndex) {
    this.setState({
      unitData: data,
      openUnitModal: true,
    });
  }

  closeRecruitUnit() {
    this.setState({
      openUnitModal: false,
      unitData: null,
    });
  }

  closeQueueData() {
    this.setState({
      openQueueData: false,
      queue: null,
      queueType: null,
    });
  }

  showBuildingSuccess(successMessage) {
    this.setState({
      buildingUpgradeSuccess: successMessage,
      showBuildingUpgradeSuccess: true,
    });
  }

  closeBuildingSuccess() {
    this.setState({
      buildingUpgradeSuccess: '',
      showBuildingUpgradeSuccess: false,
    });
  }

  showUnitRecruitmentSuccess(successMessage) {
    this.setState({
      unitRecruitmentSuccess: successMessage,
      showUnitRecruitmentSuccess: true,
    });
  }

  closeUnitRecruitmentSuccess() {
    this.setState({
      unitRecruitmentSuccess: '',
      showUnitRecruitmentSuccess: false,
    });
  }

  closeBuildingSuccess() {
    this.setState({
      buildingUpgradeSuccess: '',
      showBuildingUpgradeSuccess: false,
    });
  }

  showEditKingdomNameModal() {
    this.setState({
      openKingdomEditNameModal: true,
    });
  }

  closeEditKingdomNameModal() {
    this.setState({
      openKingdomEditNameModal: false,
    });
  }

  adjust(color, amount) {
    return '#' + color.replace(/^#/, '').replace(/../g, color =>
      ('0' + Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).substr(-2));
  }

  render() {
    if (this.state.isLoading) {
      return (
        <LoadingModal
          loadingText="Fetching Kingdom Data ..."
          show={this.props.show}
          close={this.props.close}
        />
      );
    }

    return (
      <Modal
        show={this.props.show}
        onHide={this.props.close}
        dialogClassName="large-modal"
        aria-labelledby="kingdom-management-modal"
        backdrop="static"
      >
        <Modal.Header closeButton style={{backgroundColor: this.adjust(this.state.kingdom.color, -40)}}>
          <Modal.Title id="kingdom-management-modal" style={{color: '#fff'}}>
            {this.state.kingdom.name} <i className="fas fa-edit cursor" onClick={this.showEditKingdomNameModal.bind(this)}></i>
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Tabs defaultActiveKey="info" id="kingdom-management-tabs">
            <Tab eventKey="info" title="Information">
              <div className="mt-3">
                <KingdomInfo kingdom={this.state.kingdom} characterGold={this.props.characterGold}/>
              </div>
            </Tab>
            <Tab eventKey="building-management" title="Building Management">
              <div className="mt-3">
                <AlertInfo icon={'fas fa-question-circle'} title="Info">
                  Click on the rows to open the building upgrade modal in the Buildings tab. Click Buildings in Queue to see
                  the buildings currently being upgraded/rebuilt. With in the same tab, you can click on any row, to then cancel a
                  building upgrade and get partial resources back.
                </AlertInfo>
                <Tabs defaultActiveKey="kingdom-buildings" id="kingdom-management-buildings">
                  <Tab eventKey="kingdom-buildings" title="Buildings">
                    <div className="mt-3">
                      {
                        this.state.buildingUpgradeSuccess ?
                          <div className="mb-2 mt-2">
                            <Alert variant="success" onClose={this.closeBuildingSuccess.bind(this)} dismissible>
                              {this.state.buildingUpgradeSuccess}
                            </Alert>
                          </div>
                          : null
                      }
                      <KingdomBuildings kingdom={this.state.kingdom} rowClickedHandler={this.rowClickedHandler.bind(this)}/>
                    </div>
                  </Tab>
                  <Tab eventKey="kingdom-buildings-queue" title="Buildings in Queue">
                    <div className="mt-3">
                      <KingdomBuildingQueue kingdom={this.state.kingdom} queueData={this.queueData.bind(this)}/>
                    </div>
                  </Tab>
                </Tabs>
              </div>
            </Tab>
            <Tab eventKey="unit-management" title="Unit Management">
              <div className="mt-3">
                <AlertInfo icon={'fas fa-question-circle'} title="Info">
                  Click on the rows to open the unit recruitment modal in the Recruitable Units tab. Click Units in Queue to see
                  the units currently being recruited. With in the same tab, you can click on any row, to then cancel a
                  unit recruitment and get partial resources back.
                </AlertInfo>
                <Tabs defaultActiveKey="kingdom-units" id="kingdom-management-buildings">
                  <Tab eventKey="kingdom-units" title="Recruitable Units">
                    <div className="mt-3">
                      {
                        this.state.showUnitRecruitmentSuccess ?
                          <div className="mb-2 mt-2">
                            <Alert variant="success" onClose={this.closeUnitRecruitmentSuccess.bind(this)} dismissible>
                              {this.state.unitRecruitmentSuccess}
                            </Alert>
                          </div>
                          : null
                      }
                      <KingdomUnits kingdom={this.state.kingdom} recruitUnit={this.recruitUnit.bind(this)}/>
                    </div>
                  </Tab>
                  <Tab eventKey="kingdom-units-queue" title="Units in Queue">
                    <div className="mt-3">
                      <UnitBuildingQueue kingdom={this.state.kingdom} queueData={this.unitQueueData.bind(this)} />
                    </div>
                  </Tab>
                </Tabs>
              </div>
            </Tab>
          </Tabs>
        </Modal.Body>

        {this.state.openBuildingManagement ?
          <BuildingManagementModal
            close={this.closeBuildingManagement.bind(this)}
            showBuildingSuccess={this.showBuildingSuccess.bind(this)}
            show={this.state.openBuildingManagement}
            building={this.state.building}
            kingdom={this.state.kingdom}
            characterId={this.props.characterId}
            updateKingdomData={this.props.updateKingdomData}
            queue={this.state.kingdom.building_queue}
            openTimeOutModal={this.props.openTimeOutModal.bind(this)}
            characterGold={this.props.characterGold}
          /> : null}

        {this.state.openQueueData ?
          <QueueModal
            close={this.closeQueueData.bind(this)}
            show={this.state.openQueueData}
            queueData={this.state.queue}
            buildings={this.state.kingdom.buildings}
            queueType={this.state.queueType}
            kingdom={this.state.kingdom}
            openTimeOutModal={this.props.openTimeOutModal.bind(this)}
          /> : null
        }

        {this.state.openUnitModal ?
          <RecruitUnit
            close={this.closeRecruitUnit.bind(this)}
            showUnitRecruitmentSuccess={this.showUnitRecruitmentSuccess.bind(this)}
            show={this.state.openUnitModal}
            unit={this.state.unitData}
            kingdom={this.state.kingdom}
            characterId={this.props.characterId}
            updateKingdomData={this.props.updateKingdomData}
            openTimeOutModal={this.props.openTimeOutModal.bind(this)}
            characterGold={this.props.characterGold}
          /> : null
        }

        {this.state.openKingdomEditNameModal ?
          <KingdomRenameModal
            close={this.closeEditKingdomNameModal.bind(this)}
            show={this.state.openKingdomEditNameModal}
            kingdomName={this.state.kingdom.name}
            kingdomId={this.state.kingdom.id}
            openTimeOutModal={this.props.openTimeOutModal.bind(this)}
          /> : null
        }
      </Modal>
    );
  }
}
