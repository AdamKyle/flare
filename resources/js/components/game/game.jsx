import React from 'react';
import {Row, Col} from 'react-bootstrap';
import Chat from './messages/chat';
import Map from './map/map';
import Teleport from './sections/components/teleport';
import CharacterInfoTopSection from './sections/character-info-section';
import ActionsSection from './sections/actions-section';
import PortSection from './sections/port-section';
import AdeventureActions from './sections/adventure-section';
import TraverseSection from "./sections/traverse-section";
import QuestSection from "./sections/quest-section";
import KingdomManagementModal from './kingdom/modal/kingdom-management-modal';
import KingdomSettlementModal from './kingdom/modal/kingdom-settlement-modal';
import KingdomAttackModal from './kingdom/modal/kingdom-attack-modal';
import TimeoutDialogue from "./timeout/modal/timeout-dialogue";
import NpcComponentWrapper from "./npc-components/npc-component-wrapper";
import MassEmbezzle from "./sections/modals/mass-embezzle";
import localforage from "localforage";

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
      celestial: null,
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
      openMassEmbezzlement: false,
      openQuestDetails: false,
      npcComponentName: null,
      characterId: null,
      canAdventureAgainAt: null,
      canAttack: true,
      current_x: null,
      current_y: null,
      characterGold: 0,
      inventorySets: [],
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
      attackAutomationIsRunning: false,
    }

    this.isDead            = Echo.private('character-is-dead-' + this.props.userId);
    this.npcComponent      = Echo.private('component-show-' + this.props.userId);
    this.attackAutomation  = Echo.private('attack-automation-status-' + this.props.userId);
    this.clearQuestStorage = Echo.private('clear-quest-storage-' + this.props.userId);
  }

  updateDimensions() {
    this.setState({ windowWidth: window.innerWidth});
  }

  updateCharacterGold(gold) {
    this.setState({characterGold: gold});
  }

  componentDidMount() {
    this.isDead.listen('Game.Core.Events.CharacterIsDeadBroadcastEvent', (event) => {
      this.setState({
        isDead: event.isDead,
      });
    });

    this.npcComponent.listen('Flare.Events.NpcComponentShowEvent', (event) => {
      this.openNpcComponent(event.componentName);
    });

    this.attackAutomation.listen('Game.Automation.Events.AutomatedAttackStatus', (event) => {
      this.setState({
        attackAutomationIsRunning: event.isRunning
      });
    });

    this.clearQuestStorage.listen('Game.Core.Events.ResetQuestStorageBroadcastEvent', () => {
      localforage.clear().catch((err) => console.err(err));
    });

    window.addEventListener('resize', this.updateDimensions.bind(this));
  }

  updatePort(portDetails) {
    this.setState({
      portDetails: portDetails,
    });
  }

  updateAdventure(adventureDetails, adventureLogs, adventureAgainAt, inventorySets) {
    this.setState({
      adventureDetails: adventureDetails,
      adventureLogs: adventureLogs,
      canAdventureAgainAt: adventureAgainAt,
      inventorySets: inventorySets,
    });
  }

  updatePlayerPosition(position) {
    this.setState({position: position});
  }

  updateTeleportLocations(locations, currentX, currentY) {
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
      openQuestDetails: false,
    });
  }

  openAdventureDetails(open) {
    this.setState({
      openPortDetails: false,
      openAdventureDetails: open,
      openTeleportDetails: false,
      openTraverseDetails: false,
      openQuestDetails: false,
    });
  }

  openTraverseDetails(open) {
    this.setState({
      openPortDetails: false,
      openAdventureDetails: false,
      openTeleportDetails: false,
      openTraverseDetails: open,
      openQuestDetails: false,
    });
  }

  openTeleportDetails(open) {
    this.setState({
      openTeleportDetails: open,
      openPortDetails: false,
      openAdventureDetails: false,
      openTraverseDetails: false,
      openQuestDetails: false,
    });
  }

  openQuestDetails(open) {
    this.setState({
      openQuestDetails: open,
      openPortDetails: false,
      openAdventureDetails: false,
      openTraverseDetails: false,
      openTeleportDetails: false,
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

  openMassEmbezzleModal() {
    this.setState({
      openMassEmbezzlement: !this.state.openMassEmbezzlement,
    });
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

  updateCelestial(celestial) {
    this.setState({
      celestial: celestial
    });
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

  openNpcComponent(component) {
    this.setState({
      npcComponentName: component
    });
  }

  closeNpcComponent() {
    this.setState({
      npcComponentName: null,
    });
  }

  render() {
    return (
      <>
        <div className="row">
          <div className={this.state.windowWidth <= 1900 ? "col-12" : "col-12 col-lg-9"}>
            <CharacterInfoTopSection
              characterId={this.props.characterId}
              userId={this.props.userId}
              openTimeOutModal={this.openTimeOutModal.bind(this)}
              updateCharacterGold={this.updateCharacterGold.bind(this)}
            />
            <ActionsSection
              userId={this.props.userId}
              setCharacterId={this.setCharacterId.bind(this)}
              canAttack={this.setCanAttack.bind(this)}
              openKingdomManagement={this.openKingdomManagement.bind(this)}
              openKingdomModal={this.openKingdomModal.bind(this)}
              openKingdomAttackModal={this.openKingdomAttackModal.bind(this)}
              openTimeOutModal={this.openTimeOutModal.bind(this)}
              openMassEmbezzleModal={this.openMassEmbezzleModal.bind(this)}
              updateCelestial={this.updateCelestial.bind(this)}
              celestial={this.state.celestial}
              kingdomData={this.state.kingdomData}
              character_x={this.state.current_x}
              character_y={this.state.current_y}
              attackAutomationIsRunning={this.state.attackAutomationIsRunning}
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
                  updateCelestial={this.updateCelestial.bind(this)}
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
                  inventorySets={this.state.inventorySets}
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
            {
              this.state.npcComponentName !== null ?
                <NpcComponentWrapper
                  userId={this.props.userId}
                  npcComponentName={this.state.npcComponentName}
                  close={this.closeNpcComponent.bind(this)}
                  openTimeOutModal={this.openTimeOutModal.bind(this)}
                  characterId={this.state.characterId}
                  isDead={this.state.isDead}
                />
                : null
            }
            {
              this.state.openQuestDetails ?
                <QuestSection openQuestDetails={this.openQuestDetails.bind(this)} characterId={this.state.characterId}/>
              : null
            }
          </div>
          <div
            className={
              this.state.windowWidth <= 1900 ?
                'col-12 center-element'
              : 'col-12 col-lg-3 move-map'
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
              updateTeleportLocations={this.updateTeleportLocations.bind(this)}
              openTeleportDetails={this.openTeleportDetails.bind(this)}
              openQuestDetails={this.openQuestDetails.bind(this)}
              openTimeOutModal={this.openTimeOutModal.bind(this)}
              updateKingdoms={this.updateKingdoms.bind(this)}
              updateCelestial={this.updateCelestial.bind(this)}
              attackAutomationIsRunning={this.state.attackAutomationIsRunning}
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
              characterGold={this.state.characterGold}
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
          this.state.openMassEmbezzlement ?
            <MassEmbezzle
              characterId={this.state.characterId}
              show={this.state.openMassEmbezzlement}
              close={this.openMassEmbezzleModal.bind(this)}
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
