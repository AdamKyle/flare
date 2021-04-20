import React from 'react';
import {Row, Col} from 'react-bootstrap';
import Chat from './messages/chat';
import Map from './map/map';
import Teleport from './sections/components/teleport';
import CharacterInfoTopSection from './sections/character-info-section';
import ActionsSection from './sections/actions-section';
import PortSection from './sections/port-section';
import AdeventureActions from './sections/adventure-section';
import KingdomManagementModal from './kingdom/modal/kingdom-management-modal';
import KingdomSettlementModal from './kingdom/modal/kingdom-settlement-modal';
import KingdomAttackModal from './kingdom/modal/kingdom-attack-modal';

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
      adventureDetails: [],
      adventureLogs: [],
      position: {},
      teleportLocations: {},
      openPortDetails: false,
      openAdventureDetails: false,
      openTeleportDetails: false,
      openKingdomManagement: false,
      openKingdomModal: false,
      openKingdomAttackModal: false,
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
    }
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
    });
  }

  openAdventureDetails(open) {
    this.setState({
      openPortDetails: false,
      openAdventureDetails: open,
      openTeleportDetails: false,
    });
  }

  openTeleportDetails(open) {
    this.setState({
      openTeleportDetails: open,
      openPortDetails: false,
      openAdventureDetails: false,
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
    return this.state.canAttack && this.state.portDetails.canMove;
  }

  render() {
    return (
      <>
        <Row>
          <Col xs={12} sm={12} md={12} lg={6} xl={9}>
            <CharacterInfoTopSection
              characterId={this.props.characterId}
              userId={this.props.userId}
            />
            <ActionsSection
              userId={this.props.userId}
              setCharacterId={this.setCharacterId.bind(this)}
              canAttack={this.setCanAttack.bind(this)}
              openKingdomManagement={this.openKingdomManagement.bind(this)}
              openKingdomModal={this.openKingdomModal.bind(this)}
              openKingdomAttackModal={this.openKingdomAttackModal.bind(this)}
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
                />
                : null
            }
          </Col>
          <Col xs={12} sm={12} md={12} lg={6} xl={3}>
            <Map
              apiUrl={this.apiUrl}
              userId={this.props.userId}
              updatePort={this.updatePort.bind(this)}
              position={this.state.position}
              adventures={this.state.adventureDetails}
              updatePlayerPosition={this.updatePlayerPosition.bind(this)}
              openPortDetails={this.openPortDetails.bind(this)}
              openAdventureDetails={this.openAdventureDetails.bind(this)}
              updateAdventure={this.updateAdventure.bind(this)}
              updateTeleportLoations={this.updateTeleportLoations.bind(this)}
              openTeleportDetails={this.openTeleportDetails.bind(this)}
              updateKingdoms={this.updateKingdoms.bind(this)}
            />
          </Col>
        </Row>
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
            />
            : null
        }
      </>
    )
  }
}
