import React from 'react';
import {Modal, Tab, Tabs} from 'react-bootstrap';
import BuildingManagementModal from './building-management-modal';
import QueueModal from './queue-modal';
import KingdomInfo from './partials/kingdom-info';
import KingdomBuildings from './partials/kingdom-buildings';
import KingdomBuildingQueue from './partials/kingdom-building-queue.jsx';
import UnitBuildingQueue from './partials/unit-recruitment-queue.jsx';
import KingdomUnits from './partials/kingdom-units';
import RecruitUnit from './recruit-unit';
import LoadingModal from '../../components/loading/loading-modal';

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
        }

        this.updateKingdom = Echo.private('update-kingdom-' + this.props.userId);
    }

    componentDidMount() {
        axios.get('/api/kingdoms/' + this.props.kingdomId).then((result) => {
            this.setState({
                kingdom: result.data,
                isLoading: false,
            })
        }).catch((err) => {
            console.error(err);
        });
        
        this.updateKingdom.listen('Game.Kingdoms.Events.UpdateKingdom', (event) => {
            if (this.state.kingdom.id === event.kingdom.id) {
                this.setState({
                    kingdom: event.kingdom
                });
            }
        });
    }

    adjust(color, amount) {
        return '#' + color.replace(/^#/, '').replace(/../g, color => ('0'+Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).substr(-2));
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
                <Modal.Header closeButton style={{backgroundColor: this.adjust(this.state.kingdom.color, 50)}}>
                    <Modal.Title id="kingdom-management-modal" style={{color: '#fff'}}>
                        {this.state.kingdom.name}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Tabs defaultActiveKey="manage" id="kingdom-management-tabs">
                        <Tab eventKey="manage" title="Manage">
                            <KingdomInfo kingdom={this.state.kingdom} />
                            <hr />
                            <Tabs defaultActiveKey="buildings" id="building-unit-management">
                                <Tab eventKey="buildings" title="Buildings">
                                    <KingdomBuildings kingdom={this.state.kingdom} rowClickedHandler={this.rowClickedHandler.bind(this)} />
                                </Tab>
                                <Tab eventKey="units" title="Units">
                                    <KingdomUnits kingdom={this.state.kingdom} recruitUnit={this.recruitUnit.bind(this)} />
                                </Tab>
                            </Tabs>
                        </Tab>
                        <Tab eventKey="building-queue" title="Building Queue">
                            <KingdomBuildingQueue kingdom={this.state.kingdom} queueData={this.queueData.bind(this)} />
                        </Tab>
                        <Tab eventKey="unit-queue" title="Unit Queue">
                            <UnitBuildingQueue kingdom={this.state.kingdom} queueData={this.unitQueueData.bind(this)} />
                        </Tab>
                    </Tabs>
                </Modal.Body>

                { this.state.openBuildingManagement ?
                <BuildingManagementModal
                    close={this.closeBuildingManagement.bind(this)}
                    show={this.state.openBuildingManagement}
                    building={this.state.building}
                    kingdom={this.state.kingdom}
                    characterId={this.props.characterId}
                    updateKingdomData={this.props.updateKingdomData}
                    queue={this.state.kingdom.building_queue}
                /> : null }

                { this.state.openQueueData ? 
                    <QueueModal
                        close={this.closeQueueData.bind(this)}
                        show={this.state.openQueueData}
                        queueData={this.state.queue}
                        buildings={this.state.kingdom.buildings}
                        queueType={this.state.queueType}
                        kingdom={this.state.kingdom}
                    />: null
                }

                { this.state.openUnitModal ?
                    <RecruitUnit
                        close={this.closeRecruitUnit.bind(this)}
                        show={this.state.openUnitModal}
                        unit={this.state.unitData}
                        kingdom={this.state.kingdom}
                        characterId={this.props.characterId}
                        updateKingdomData={this.props.updateKingdomData}
                    /> : null
                }
            </Modal>
        );
    }
}