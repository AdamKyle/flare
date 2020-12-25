import React               from 'react';
import ReactDOM            from 'react-dom';
import Chat                from './messages/chat';
import Map                 from './map/map';
import Teleport            from './map/components/teleport';
import CharacterInfoTopBar from './components/character-info-top-bar';
import CoreActionsSection  from './components/core-actions-section';
import PortLocationActions from './components/port-location-actions';
import AdeventureActions   from './components/adventure-actions';
import AdventureMenu       from './components/menu/adventure-menu';
import NotificationCenter  from './components/nav-bar/notification-center';
import RefreshComponent    from './components/refresh-component';
import Management from './kingdom/management';

class Game extends React.Component {
  constructor(props) {
    super(props);

    this.apiUrl = window.location.protocol + '//' + window.location.host + '/api/';

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
      characterId: null,
      canAdventureAgainAt: null,
      canAttack: true,
      current_x: null,
      current_y: null,
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

  openKingdomManagement(open) {
    this.setState({openKingdomManagement: open});
  }

  setCanAttack(bool) {
    this.setState({
      canAttack: bool,
    });
  }

  canAdventure() {
    return this.state.canAttack && this.state.portDetails.canMove;
  }

  render() {
    return (
      <>
        <div className="row mb-4">
          <div className="col-md-12">
            <div className="row">
              <div className="col-md-9">
                <CharacterInfoTopBar apiUrl={this.apiUrl} characterId={this.props.characterId} userId={this.props.userId}/>
                <CoreActionsSection apiUrl={this.apiUrl} userId={this.props.userId} setCharacterId={this.setCharacterId.bind(this)} canAttack={this.setCanAttack.bind(this)} openKingdomManagement={this.openKingdomManagement.bind(this)}/>
                {this.state.openPortDetails ? <PortLocationActions updateAdventure={this.updateAdventure.bind(this)} portDetails={this.state.portDetails} userId={this.props.userId} openPortDetails={this.openPortDetails.bind(this)} updatePlayerPosition={this.updatePlayerPosition.bind(this)}/> : null}
                {this.state.openAdventureDetails ? <AdeventureActions canAdventure={this.canAdventure.bind(this)} updateAdventure={this.updateAdventure.bind(this)} adventureDetails={this.state.adventureDetails} userId={this.props.userId} characterId={this.state.characterId} openAdventureDetails={this.openAdventureDetails.bind(this)} adventureAgainAt={this.state.canAdventureAgainAt} adventureLogs={this.state.adventureLogs} /> : null}
                {this.state.openTeleportDetails ? <Teleport teleportLocations={this.state.teleportLocations} openTeleportDetails={this.openTeleportDetails.bind(this)} currentX={this.state.current_x} currentY={this.state.current_y} characterId={this.props.characterId}/> : null}
                {this.state.openKingdomManagement ? <Management openKingdomManagement={this.openKingdomManagement.bind(this)} /> : null}
              </div>
              <div className="col-md-3">
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
                />
              </div>
            </div>
          </div>
        </div>
        <div className="row">
          <div className="col-md-12">
            <Chat apiUrl={this.apiUrl} userId={this.props.userId}/>
          </div>
        </div>
      </>
    )
  }
}

// Mount the app:
const game      = document.getElementById('game');
const adminChat = document.getElementById('admin-chat');
const refresh   = document.getElementById('refresh');
const player    = document.head.querySelector('meta[name="player"]');
const character = document.head.querySelector('meta[name="character"]');

if (refresh !== null) {
  ReactDOM.render(
      <RefreshComponent userId={parseInt(player.content)}/>,
      refresh
  );
}

if (game !== null) {
  ReactDOM.render(
      <Game userId={parseInt(player.content)} characterId={character.content}/>,
      game
  );
}

if (adminChat !== null) {
    ReactDOM.render(
      <Chat userId={parseInt(player.content)} />,
      adminChat
  );
}

// Mount the Menu Components for the Side Menu:
const adventureMenu = document.getElementById('adventure-menu');

if (adventureMenu !== null) {
  ReactDOM.render(
      <AdventureMenu userId={parseInt(player.content)} />,
      adventureMenu
  );
}

// Mount the Notification Center:

const notificationCenter = document.getElementById('notification-center');

if (notificationCenter !== null) {
  ReactDOM.render(
      <NotificationCenter userId={parseInt(player.content)} />,
      notificationCenter
  );
}