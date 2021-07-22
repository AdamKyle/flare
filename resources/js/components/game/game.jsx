import React from 'react';
import {Row, Col, Container} from 'react-bootstrap';
import Chat from './messages/chat';
import Map from './map/map';
import Teleport from './sections/components/teleport';
import CharacterInfoTopSection from './sections/character-info-section';
import ActionsSection from './sections/actions-section';
import PortSection from './sections/port-section';
import AdeventureActions from './sections/adventure-section';
import TraverseSection from "./sections/traverse-section";
import KingdomManagementModal from './kingdom/modal/kingdom-management-modal';
import KingdomSettlementModal from './kingdom/modal/kingdom-settlement-modal';
import KingdomAttackModal from './kingdom/modal/kingdom-attack-modal';
import TimeoutDialogue from "./timeout/modal/timeout-dialogue";

export default class Game extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      portDetails: {
        currentPort: null,
        portList: [],
        characterId: null,
        isDead: false,
        canMove: true,
      },
      timeOutFor: 0,
      adventureDetails: [],
      adventureLogs: [],
      position: {},
      teleportLocations: {},
      openPortDetails: false,
      openAdventureDetails: false,
      openTeleportDetails: false,
      openTraverseDetails: false,
      openKingdomManagement: false,
      openKingdomModal: false,
      openKingdomAttackModal: false,
      openTimeOutModal: false,
      characterId: null,
      canAdventureAgainAt: null,
      canAttack: true,
      current_x: null,
      current_y: null,
      kingdomData: {
        my_kingdoms: [],
        can_attack: false,
        can_settle: false,
        kingdom_to_attack: [],
        is_mine: false,
      },
      kingdom: null,
      isDead: false,
      windowWidth: window.innerWidth,
    }

    this.isDead = Echo.private('character-is-dead-' + this.props.userId);
  }

  updateDimensions() {
    this.setState({ windowWidth: window.innerWidth});
  }

  componentDidMount() {
    this.isDead.listen('Game.Core.Events.CharacterIsDeadBroadcastEvent', (event) => {
      this.setState({
        isDead: event.isDead,
      });
    });

    window.addEventListener('resize', this.updateDimensions.bind(this));
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.updateDimensions.bind(this));
  }

  updatePort(portDetails) {
    this.setState({
      portDetails: portDetails,
    });
  }

  updateAdventure(adventureDetails, adventureLogs, adventureAgainAt) {
    this.setState({
      adventureDetails: adventureDetails,
      adventureLogs: adventureLogs,
      canAdventureAgainAt: adventureAgainAt,
    });
  }

  updatePlayerPosition(position) {
    this.setState({position: position});
  }

  updateTeleportLoations(locations, currentX, currentY) {
    this.setState({
      teleportLocations: locations,
      current_x: currentX,
      current_y: currentY,
    });
  }

  openPortDetails(open) {
    this.setState({
      openPortDetails: open,
      openAdventureDetails: false,
      openTeleportDetails: false,
      openTraverseDetails: false,
    });
  }

  openAdventureDetails(open) {
    this.setState({
      openPortDetails: false,
      openAdventureDetails: open,
      openTeleportDetails: false,
      openTraverseDetails: false,
    });
  }

  openTraverseDetails(open) {
    this.setState({
      openPortDetails: false,
      openAdventureDetails: false,
      openTeleportDetails: false,
      openTraverseDetails: open,
    });
  }

  openTeleportDetails(open) {
    this.setState({
      openTeleportDetails: open,
      openPortDetails: false,
      openAdventureDetails: false,
      openTraverseDetails: false,
    });
  }

  setCharacterId(characterId) {
    this.setState({characterId: characterId});
  }

  openKingdomManagement() {
    const kingdom = this.state.kingdomData.my_kingdoms.filter((mk) =>
      mk.x_position === this.state.current_x &&
      mk.y_position === this.state.current_y
    );

    if (kingdom.length > 0) {
      this.setState({
        openKingdomManagement: true,
        kingdom: kingdom[0],
      });
    }
  }

  closeKingdomManagement() {
    this.setState({
      openKingdomManagement: false,
      kingdom: null,
    })
  }

  setCanAttack(bool) {
    this.setState({
      canAttack: bool,
    });
  }

  updateKingdoms(kingdomData) {
    this.setState({
      kingdomData: kingdomData,
    });
  }

  openKingdomModal() {
    this.setState({
      openKingdomModal: true,
    });
  }

  closeKingdomModal() {
    this.setState({
      openKingdomModal: false,
    });
  }

  openKingdomAttackModal() {
    this.setState({
      openKingdomAttackModal: true,
    });
  }

  closeKingdomAttackModal() {
    this.setState({
      openKingdomAttackModal: false,
    });
  }

  updateKingdomData(kingdom) {
    const index = this.state.kingdomData.my_kingdoms.findIndex((mk) =>
      mk.x_position === this.state.current_x &&
      mk.y_position === this.state.current_y
    );

    if (index !== -1) {
      let kingdomData = _.cloneDeep(this.state.kingdomData);

      kingdomData.my_kingdoms[index] = kingdom;

      this.setState({
        kingdomData: kingdomData,
        kingdom: kingdom,
        openKingdomManagement: true,
      });
    } else {
      let kingdomData = _.cloneDeep(this.state.kingdomData);

      kingdomData.my_kingdoms.push(kingdom);

      this.setState({
        kingdomData: kingdomData,
        kingdom: kingdom,
        openKingdomManagement: true,
      });
    }
  }

  canAdventure() {
    if (this.state.isDead) {
      return false;
    }

    if (!this.state.canAttack || !this.state.portDetails.canMove) {
      return false;
    }

    return true;
  }

  openTimeOutModal() {
    if (!this.state.openTimeOutModal) {
      this.setState({
        openTimeOutModal: true,
        timeOutFor: 0
      });
    }
  }

  render() {
    console.log(this.state.windowWidth);
    return (
      <>
        <div className="row">
          <div className="col-12 col-lg-6 col-xl-9 col-xxl-12 col-xxxl-9">
            <CharacterInfoTopSection
              characterId={this.props.characterId}
              userId={this.props.userId}
              openTimeOutModal={this.openTimeOutModal.bind(this)}
            />
            <ActionsSection
              userId={this.props.userId}
              setCharacterId={this.setCharacterId.bind(this)}
              canAttack={this.setCanAttack.bind(this)}
              openKingdomManagement={this.openKingdomManagement.bind(this)}
              openKingdomModal={this.openKingdomModal.bind(this)}
              openKingdomAttackModal={this.openKingdomAttackModal.bind(this)}
              openTimeOutModal={this.openTimeOutModal.bind(this)}
              kingdomData={this.state.kingdomData}
              character_x={this.state.current_x}
              character_y={this.state.current_y}
            />
            {
              this.state.openPortDetails ?
                <PortSection
                  updateAdventure={this.updateAdventure.bind(this)}
                  portDetails={this.state.portDetails}
                  userId={this.props.userId}
                  openPortDetails={this.openPortDetails.bind(this)}
                  updatePlayerPosition={this.updatePlayerPosition.bind(this)}
                  openTimeOutModal={this.openTimeOutModal.bind(this)}
                />
                : null
            }
            {
              this.state.openAdventureDetails ?
                <AdeventureActions
                  canAdventure={this.canAdventure.bind(this)}
                  updateAdventure={this.updateAdventure.bind(this)}
                  adventureDetails={this.state.adventureDetails}
                  userId={this.props.userId}
                  characterId={this.state.characterId}
                  openAdventureDetails={this.openAdventureDetails.bind(this)}
                  adventureAgainAt={this.state.canAdventureAgainAt}
                  adventureLogs={this.state.adventureLogs}
                  openTimeOutModal={this.openTimeOutModal.bind(this)}
                />
                : null
            }
            {
              this.state.openTeleportDetails ?
                <Teleport
                  teleportLocations={this.state.teleportLocations}
                  openTeleportDetails={this.openTeleportDetails.bind(this)}
                  currentX={this.state.current_x}
                  currentY={this.state.current_y}
                  characterId={this.props.characterId}
                  openTimeOutModal={this.openTimeOutModal.bind(this)}
                />
                : null
            }
            {
              this.state.openTraverseDetails ?
                <TraverseSection
                  openTraverseSection={this.openTraverseDetails.bind(this)}
                  characterId={this.state.characterId}
                  openTimeOutModal={this.openTimeOutModal.bind(this)}
                />
                : null
            }
          </div>
          <div
            className={
              this.state.windowWidth === 1366 ?
                'col-12 col-sm-8 col-lg-6 col-xl-3 col-xxl-5 col-xxxl-3 center-element'
              : 'col-12 col-sm-8 col-lg-6 col-xl-3 col-xxl-5 col-xxxl-3'
            }
          >
            <Map
              apiUrl={this.apiUrl}
              userId={this.props.userId}
              updatePort={this.updatePort.bind(this)}
              position={this.state.position}
              adventures={this.state.adventureDetails}
              updatePlayerPosition={this.updatePlayerPosition.bind(this)}
              openPortDetails={this.openPortDetails.bind(this)}
              openAdventureDetails={this.openAdventureDetails.bind(this)}
              openTraverserDetails={this.openTraverseDetails.bind(this)}
              updateAdventure={this.updateAdventure.bind(this)}
              updateTeleportLoations={this.updateTeleportLoations.bind(this)}
              openTeleportDetails={this.openTeleportDetails.bind(this)}
              openTimeOutModal={this.openTimeOutModal.bind(this)}
              updateKingdoms={this.updateKingdoms.bind(this)}
            />
          </div>
        </div>
        <Row>
          <Col xs={12}>
            <Chat apiUrl={this.apiUrl} userId={this.props.userId}/>
          </Col>
        </Row>

        {
          this.state.openKingdomManagement ?
            <KingdomManagementModal
              show={this.state.openKingdomManagement}
              close={this.closeKingdomManagement.bind(this)}
              kingdomId={this.state.kingdom.id}
              updateKingdomData={this.updateKingdomData.bind(this)}
              characterId={this.state.characterId}
              userId={this.props.userId}
              openTimeOutModal={this.openTimeOutModal.bind(this)}
            />
            : null
        }

        {
          this.state.openKingdomModal ?
            <KingdomSettlementModal
              characterId={this.state.characterId}
              show={this.state.openKingdomModal}
              x={this.state.current_x}
              y={this.state.current_y}
              close={this.closeKingdomModal.bind(this)}
              openKingdomManagement={this.openKingdomManagement.bind(this)}
              myKingomsCount={this.state.kingdomData.my_kingdoms.length}
              openTimeOutModal={this.openTimeOutModal.bind(this)}
            />
            : null
        }

        {
          this.state.openKingdomAttackModal ?
            <KingdomAttackModal
              show={this.state.openKingdomAttackModal}
              close={this.closeKingdomAttackModal.bind(this)}
              kingdoms={this.state.kingdomData.my_kingdoms}
              kingdomToAttack={this.state.kingdomData.kingdom_to_attack}
              characterId={this.state.characterId}
              openTimeOutModal={this.openTimeOutModal.bind(this)}
            />
            : null
        }
        {
          this.state.openTimeOutModal ?
            <TimeoutDialogue userId={this.props.userId} show={this.state.openTimeOutModal} timeOutFor={this.state.timeOutFor}/> : null
        }
        </>
    )
  }
}
