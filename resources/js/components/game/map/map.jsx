import React from 'react';
import Draggable from 'react-draggable';
import {getServerMessage} from '../helpers/server_message';
import {
  getNewXPosition,
  getNewYPosition,
  dragMap
} from './helpers/map_position';
import CardLoading from '../components/loading/card-loading';
import MapMovementActions from './components/map-movement-actions';
import MapActions from './components/map-actions';
import Locations from './components/locations';
import KingdomPin from './components/pins/kingdom-pin';
import NpcKingdomPin from "./components/pins/npc-kingdom-pin";
import EnemyKingdomPin from "./components/pins/enemy-kingdom-pin";

export default class Map extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      controlledPosition: {
        x: 0, y: 0
      },
      characterPosition: {
        x: 16, y: 32
      },
      charactersOnMap: 0,
      mapUrl: null,
      bottomBounds: 0,
      rightBounds: 0,
      isLoading: true,
      characterId: 0,
      windowWidth: 0,
      showCharacterInfo: false,
      canMove: true,
      showMessage: false,
      locations: null,
      currentPort: null,
      adventures: [],
      portList: [],
      adventureLogs: [],
      teleportLocations: [],
      npcKingdoms: [],
      celestials: [],
      canAdventureAgainAt: null,
      timeRemaining: null,
      isDead: false,
      isAdventuring: false,
      kingdoms: [],
      characterMapName: null,
      otherKingdoms: [],
      mapIsOpen: false,
    }

    this.echo = Echo.private('show-timeout-move-' + this.props.userId);
    this.isDead = Echo.private('character-is-dead-' + this.props.userId);
    this.adventureLogs = Echo.private('update-adventure-logs-' + this.props.userId);
    this.updateMap = Echo.private('update-map-' + this.props.userId);
    this.addKingdomToMap = Echo.private('add-kingdom-to-map-' + this.props.userId);
    this.updateMapPlane = Echo.private('update-map-plane-' + this.props.userId);
    this.globalCharacterCount = Echo.join('global-character-count-plane');
    this.globalMapUpdate = Echo.join('global-map-update');
    this.globaNPCKingdomUpdate = Echo.join('npc-kingdoms-update');
    this.enemyKingdomMoraleUpdate = Echo.join('enemy-kingdom-morale-update');
  }

  componentDidMount() {

    axios.get('/api/map/' + this.props.userId).then((result) => {
      this.setState({
        mapUrl: result.data.map_url,
        controlledPosition: {
          x: getNewXPosition(result.data.character_map.character_position_x, result.data.character_map.position_x),
          y: getNewYPosition(result.data.character_map.character_position_y, result.data.character_map.position_y),
        },
        characterPosition: {
          x: result.data.character_map.character_position_x,
          y: result.data.character_map.character_position_y,
        },
        characterId: result.data.character_id,
        isLoading: false,
        canMove: result.data.can_move,
        showMessage: result.data.show_message,
        locations: result.data.locations,
        currentPort: result.data.port_details !== null ? result.data.port_details.current_port : null,
        adventures: result.data.adventure_details !== null ? result.data.adventure_details : [],
        portList: result.data.port_details !== null ? result.data.port_details.port_list : [],
        timeRemaining: result.data.timeout !== null ? result.data.timeout : null,
        isDead: result.data.is_dead,
        adventureLogs: result.data.adventure_logs,
        canAdventureAgainAt: result.data.adventure_completed_at,
        isAdventuring: !_.isEmpty(result.data.adventure_logs.filter(al => al.in_progress)),
        teleportLocations: result.data.teleport,
        kingdoms: result.data.my_kingdoms,
        npcKingdoms: result.data.npc_kingdoms,
        otherKingdoms: result.data.other_kingdoms,
        charactersOnMap: result.data.characters_on_map,
        characterMapName: result.data.character_map.game_map.name,
      }, () => {
        this.props.updatePort({
          currentPort: this.state.currentPort,
          portList: this.state.portList,
          characterId: this.state.characterId,
          isDead: this.state.isDead,
          canMove: this.state.canMove,
        });

        this.props.updateAdventure(this.state.adventures, this.state.adventureLogs, this.state.canAdventureAgainAt, result.data.inventory_sets);

        this.props.updateTeleportLocations(this.state.teleportLocations, this.state.characterPosition.x, this.state.characterPosition.y);

        this.props.updateKingdoms({
          my_kingdoms: this.state.kingdoms,
          can_attack: result.data.can_attack_kingdom,
          can_settle: result.data.can_settle_kingdom,
          is_mine: this.isMyKingdom(this.state.kingdoms, this.state.characterPosition),
          kingdom_to_attack: result.data.kingdom_to_attack,
          can_mass_embezzle: result.data.can_mass_embezzle
        });

        this.props.updateLockedLocationType(result.data.lockedLocationType);

        if (result.data.celestials.length > 0) {
          this.props.updateCelestial(result.data.celestials[0]);
        }
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal()
        }
      }
    });

    this.globaNPCKingdomUpdate.listen('Game.Kingdoms.Events.UpdateNPCKingdoms', (event) => {
      if (event.mapName === this.state.characterMapName) {
        this.setState({
          npcKingdoms: event.npcKingdoms,
        });
      }
    });

    this.globalMapUpdate.listen('Game.Kingdoms.Events.UpdateGlobalMap', (event) => {
      if (event.mapName === this.state.characterMapName) {
        this.setState({
          otherKingdoms: event.otherKingdoms.filter((ok) => ok.character_id !== this.state.characterId),
        });
      }
    });

    this.enemyKingdomMoraleUpdate.listen('Game.Kingdoms.Events.UpdateEnemyKingdomsMorale', (event) => {

      if (this.state.otherKingdoms.length > 0) {
        let otherKingdoms = this.state.otherKingdoms;

        const index = otherKingdoms.findIndex((ok) => ok.id === event.enemyMorale.id);

        if (index !== -1) {
          otherKingdoms[index].current_morale = event.enemyMorale.current_morale;
        }

        this.setState({
          otherKingdoms: otherKingdoms,
        });
      }
    });

    this.globalCharacterCount.listen('Game.Maps.Events.UpdateGlobalCharacterCountBroadcast', (event) => {
      if (event.mapName === this.state.characterMapName) {
        this.setState({
          charactersOnMap: event.characterCount,
        });
      }
    });

    this.echo.listen('Game.Maps.Events.ShowTimeOutEvent', (event) => {

      this.setState({
        canMove: event.canMove,
        showMessage: false,
        timeRemaining: event.forLength !== 0 ? (event.forLength * 60) : 10,
      }, () => {
        this.props.updatePort({
          currentPort: this.state.currentPort,
          portList: this.state.portList,
          characterId: this.state.characterId,
          isDead: this.state.isDead,
          canMove: event.canMove,
        });
      });
    });

    this.isDead.listen('Game.Core.Events.CharacterIsDeadBroadcastEvent', (event) => {
      this.setState({
        isDead: event.isDead
      });
    });

    this.adventureLogs.listen('Game.Adventures.Events.UpdateAdventureLogsBroadcastEvent', (event) => {
      this.setState({
        isAdventuring: event.isAdventuring,
        canMove: event.user.character.can_move,
      }, () => {
        this.props.updateAdventure(this.state.adventures, event.adventureLogs, event.canAdAdventureAgainAt);
      });
    });

    this.updateMap.listen('Game.Maps.Events.UpdateMapDetailsBroadcast', (event) => {
      if (event.map.game_map.name !== this.state.characterMapName && !event.pctCommand) {
        return;
      }

      this.updatePlayerPosition(event.map);
      let myKingdoms = this.fetchKingdoms(event);

      this.setState({
        currentPort: event.portDetails.hasOwnProperty('current_port') ? event.portDetails.current_port : null,
        portList: event.portDetails.hasOwnProperty('port_list') ? event.portDetails.port_list : [],
        adventures: event.adventureDetails,
        kingdoms: myKingdoms,
        npcKingdoms: event.npcKingdoms,
      }, () => {
        this.props.updateAdventure(event.adventureDetails, [], null);

        this.props.updateTeleportLocations(this.state.teleportLocations, event.map.character_position_x, event.map.character_position_y);

        this.props.updatePort({
          currentPort: event.portDetails.hasOwnProperty('current_port') ? event.portDetails.current_port : null,
          portList: event.portDetails.hasOwnProperty('port_list') ? event.portDetails.port_list : [],
          characterId: this.state.characterId,
          isDead: this.state.isDead,
          canMove: this.state.canMove,
        });

        this.props.updateKingdoms({
          my_kingdoms: myKingdoms,
          can_attack: event.kingdomDetails.hasOwnProperty('can_attack') ? event.kingdomDetails.can_attack : false,
          can_settle: event.kingdomDetails.hasOwnProperty('can_settle') ? event.kingdomDetails.can_settle : false,
          is_mine: this.isMyKingdom(myKingdoms, this.state.characterPosition),
          kingdom_to_attack: event.kingdomDetails.kingdom_to_attack,
          can_mass_embezzle: event.canMassEmbezzle,
        });

        if (_.isEmpty(event.portDetails)) {
          this.props.openPortDetails(false);
        }

        if (_.isEmpty(event.adventureDetails)) {
          this.props.openAdventureDetails(false);
        }

        if (event.celestials.length > 0) {
          this.props.updateCelestial(event.celestials[0]);
        } else {
          this.props.updateCelestial(null);
        }
      });
    });

    this.addKingdomToMap.listen('Game.Kingdoms.Events.AddKingdomToMap', (event) => {
      this.setState({
        kingdoms: event.kingdoms,
        npcKingdoms: event.npcKingdoms,
      }, () => {
        this.props.updateKingdoms({
          my_kingdoms: this.state.kingdoms,
          can_attack: false,
          can_settle: false,
          is_mine: true,
        });
      });
    });

    this.updateMapPlane.listen('Game.Maps.Events.UpdateMapBroadcast', (event) => {
      const myKingdoms = event.mapDetails.my_kingdoms;

      this.setState({
        mapUrl: event.mapDetails.map_url,
        locations: event.mapDetails.locations,
        kingdoms: event.mapDetails.my_kingdoms,
        npcKingdoms: event.mapDetails.npc_kingdoms,
        portList: event.mapDetails.port_details !== null ? event.mapDetails.port_details.port_list : [],
        adventures: event.mapDetails.adventure_details,
        currentPort: event.mapDetails.port_details !== null ? event.mapDetails.port_details.current_port : null,
        controlledPosition: {
          x: getNewXPosition(event.mapDetails.character_map.character_position_x, event.mapDetails.character_map.position_x),
          y: getNewYPosition(event.mapDetails.character_map.character_position_y, event.mapDetails.character_map.position_y),
        },
        characterPosition: {
          x: event.mapDetails.character_map.character_position_x,
          y: event.mapDetails.character_map.character_position_y,
        },
        charactersOnMap: event.mapDetails.characters_on_map,
        otherKingdoms: event.mapDetails.other_kingdoms,
        characterMapName: event.mapDetails.map_name,
      }, () => {
        this.props.updateKingdoms({
          my_kingdoms: myKingdoms,
          can_attack: event.mapDetails.my_kingdoms.hasOwnProperty('can_attack') ? event.mapDetails.my_kingdoms.can_attack : false,
          can_settle: event.mapDetails.can_settle_kingdom,
          is_mine: this.isMyKingdom(myKingdoms, this.state.characterPosition),
          kingdom_to_attack: event.mapDetails.kingdom_to_attack
        });

        this.props.updatePort({
          currentPort: this.state.currentPort,
          portList: this.state.portList,
          characterId: this.state.characterId,
          isDead: this.state.isDead,
          canMove: this.state.canMove,
        });

        this.props.updateAdventure(this.state.adventures, this.state.adventureLogs, this.state.canAdventureAgainAt);

        this.props.updateTeleportLocations(
          this.state.teleportLocations,
          event.mapDetails.character_map.character_position_x,
          event.mapDetails.character_map.character_position_y
        );

        if (event.mapDetails.celestials.length > 0) {
          this.props.updateCelestial(event.mapDetails.celestials[0]);
        } else {
          this.props.updateCelestial(null);
        }

        this.props.updatePlayerPosition()
      });
    });

    this.setState({
      windowWidth: window.innerWidth,
      mapIsOpen: window.innerWidth > 1900 ? true : false,
    });

    window.addEventListener("resize", this.updateWidth.bind(this));
  }

  updateWidth() {
    this.setState({
      windowWidth: window.innerWidth,
      mapIsOpen: window.innerWidth > 1900 ? true : false,
    });
  }

  getMaxLeft() {
    return -110;
  }

  componentDidUpdate() {
    if (!_.isEmpty(this.props.position)) {
      this.updatePlayerPosition(this.props.position);
    }

    if (this.props.adventures !== this.state.adventures) {
      this.setState({
        adventures: this.props.adventures
      });
    }
  }

  fetchKingdoms(event) {
    if (event.hasOwnProperty('updatedKingdoms')) {
      if (event.updatedKingdoms.hasOwnProperty('kingdom_details')) {
        return event.updatedKingdoms.kingdom_details;
      }
    }

    return this.state.kingdoms;
  }

  isMyKingdom(kingdoms, characterPosition) {
    const found = kingdoms.filter((k) => k.x_position === characterPosition.x && k.y_position === characterPosition.y);

    if (found.length > 0) {
      return true;
    }

    return false;
  }

  handleDrag(e, position) {
    this.setState(dragMap(
      position, this.state.bottomBounds, this.state.rightBounds
    ));
  }

  playerIcon() {
    return {
      top: this.state.characterPosition.y + 'px',
      left: this.state.characterPosition.x + 'px',
    }
  }

  openPortDetails() {
    this.props.openPortDetails(true);
  }

  openAdventureDetails() {
    this.props.openAdventureDetails(true);
  }

  openTeleport() {
    this.props.openTeleportDetails(true);
  }

  openTraverse() {
    this.props.openTraverserDetails(true);
  }

  openQuest() {
    this.props.openQuestDetails(true);
  }

  disableMapButtons() {
    return this.state.isDead || this.state.isAdventuring || !this.state.canMove;
  }

  updatePlayerPosition(position) {
    const characterX = position.character_position_x;
    const characterY = position.character_position_y;
    const mapX = position.position_x;
    const mapY = position.position_y;

    this.setState({
      characterPosition: {x: characterX, y: characterY},
      controlledPosition: {x: getNewXPosition(characterX, mapX), y: getNewYPosition(characterY, mapY)},
    }, () => {
      this.props.updatePlayerPosition({});
    });
  }

  manageMap() {
    this.setState({
      mapIsOpen: !this.state.mapIsOpen,
    })
  }

  move(coordinates) {
    if (!this.state.canMove) {
      return getServerMessage('cant_move');
    }

    if (this.state.isDead) {
      return getServerMessage('dead_character');
    }

    const x = coordinates.x;
    const y = coordinates.y;

    this.props.updateCelestial(null)

    axios.post('/api/move/' + this.state.characterId, {
      position_x: this.state.controlledPosition.x,
      position_y: this.state.controlledPosition.y,
      character_position_x: x,
      character_position_y: y,
    }).then((result) => {
      this.setState({
        currentPort: result.data.port_details.hasOwnProperty('current_port') ? result.data.port_details.current_port : null,
        portList: result.data.port_details.hasOwnProperty('port_list') ? result.data.port_details.port_list : [],
        adventures: result.data.adventure_details,
        characterPosition: {x, y},
        controlledPosition: {
          x: getNewXPosition(x, this.state.controlledPosition.x),
          y: getNewYPosition(y, this.state.controlledPosition.y)
        },
        charactersOnMap: result.data.characters_on_map,
      }, () => {
        this.props.updatePort({
          currentPort: this.state.currentPort,
          portList: this.state.portList,
          characterId: this.state.characterId,
          isDead: this.state.isDead,
          canMove: this.state.canMove,
        });

        this.props.updateKingdoms({
          my_kingdoms: this.state.kingdoms,
          can_attack: result.data.kingdom_details.can_attack,
          can_settle: result.data.kingdom_details.can_settle,
          is_mine: result.data.kingdom_details.can_manage,
          kingdom_to_attack: result.data.kingdom_details.kingdom_to_attack,
          can_mass_embezzle: result.data.can_mass_embezzle,
        });

        this.props.updateTeleportLocations(this.state.teleportLocations, this.state.characterPosition.x, this.state.characterPosition.y);

        this.props.updateAdventure(this.state.adventures, [], null);

        this.props.updateLockedLocationType(result.data.lockedLocationType);

        if (this.state.currentPort == null) {
          this.props.openPortDetails(false);
        }

        if (_.isEmpty(this.state.adventures)) {
          this.props.openAdventureDetails(false);
        }

        if (result.data.celestials.length > 0) {
          this.props.updateCelestial(result.data.celestials[0]);
        } else {
          this.props.updateCelestial(null);
        }
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }

        return getServerMessage('cannot_walk_on_water', response.data.message);
      }

      this.setState({
        characterPosition: {x: this.state.characterPosition.x, y: this.state.characterPosition.y},
      });
    });
  }

  render() {
    if (this.state.isLoading) {
      return <CardLoading/>
    }

    return (
      <div className="card mb-4 map-card">
        <button
          className={this.state.windowWidth < 1900 ? 'btn btn-primary btn-sm' : 'hide'}
          onClick={this.manageMap.bind(this)}
        >
          {this.state.mapIsOpen ? 'Close Map' : 'Show Map'}
        </button>
        <div className={this.state.mapIsOpen ? "card-body" : 'hide'}>
          <div className="map-body">
            <Draggable
              position={this.state.controlledPosition}
              bounds={{top: -160, left: this.getMaxLeft(), right: this.state.rightBounds, bottom: this.state.bottomBounds}}
              handle=".handle"
              defaultPosition={{x: 0, y: 0}}
              grid={[16, 16]}
              scale={1}
              onStart={this.handleStart}
              onDrag={this.handleDrag.bind(this)}
              onStop={this.handleStop}
            >
              <div>
                <div className="handle game-map"
                     style={{backgroundImage: `url("${this.state.mapUrl}")`, width: 500, height: 500}}>
                  <Locations locations={this.state.locations}/>
                  <KingdomPin
                    kingdoms={this.state.kingdoms}
                    characterId={this.state.characterId}
                    disableMapButtons={this.disableMapButtons.bind(this)}
                    openTimeOutModal={this.props.openTimeOutModal}
                  />
                  <NpcKingdomPin
                    npcKingdoms={this.state.npcKingdoms}
                  />
                  <EnemyKingdomPin
                    kingdoms={this.state.otherKingdoms}
                    characterId={this.state.characterId}
                    disableMapButtons={true}
                  />
                  <div className="map-x-pin" style={this.playerIcon()}></div>
                </div>
              </div>
            </Draggable>
          </div>
          <div className="character-position mt-2">
            <div className="mb-2 mt-2 clearfix">
              <MapActions
                adventures={this.state.adventures}
                currentPort={this.state.currentPort}
                characterPosition={this.state.characterPosition}
                disableMapButtons={this.disableMapButtons.bind(this)}
                openAdventureDetails={this.openAdventureDetails.bind(this)}
                openPortDetails={this.openPortDetails.bind(this)}
                openTeleport={this.openTeleport.bind(this)}
                openQuest={this.openQuest.bind(this)}
                charactersOnMap={this.state.charactersOnMap}
                attackAutomationIsRunning={this.props.attackAutomationIsRunning}
              />
            </div>
          </div>
          <hr/>
          <MapMovementActions
            isDead={this.state.isDead}
            isAdventuring={this.state.isAdventuring}
            disableMapButtons={this.disableMapButtons.bind(this)}
            openTraverse={this.openTraverse.bind(this)}
            characterPosition={this.state.characterPosition}
            timeRemaining={this.state.timeRemaining}
            move={this.move.bind(this)}
            userId={this.props.userId}
            attackAutomationIsRunning={this.props.attackAutomationIsRunning}
          />
        </div>
      </div>
    )
  }
}
