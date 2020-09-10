import React               from 'react';
import ReactDOM            from 'react-dom';
import Chat                from './messages/chat';
import Map                 from './map/map';
import CharacterInfoTopBar from './components/character-info-top-bar';
import CoreActionsSection  from './components/core-actions-section';
import PortLocationActions from './components/port-location-actions';
import AdeventureActions   from './components/adventure-actions';
import AdventureMenu       from './components/menu/adventure-menu';

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
      openPortDetails: false,
      openAdventureDetails: false,
      characterId: null,
      canAdventureAgainAt: null,
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

  openPortDetails(open) {
    this.setState({
      openPortDetails: open,
      openAdventureDetails: false,
    });
  }

  openAdventureDetails(open) {
    this.setState({
      openPortDetails: false,
      openAdventureDetails: open,
    });
  }

  setCharacterId(characterId) {
    this.setState({characterId: characterId});
  }

  render() {
    return (
      <>
        <div className="row mb-4">
          <div className="col-md-12">
            <div className="row">
              <div className="col-md-8">
                <CharacterInfoTopBar apiUrl={this.apiUrl} characterId={this.props.characterId} userId={this.props.userId}/>
                <CoreActionsSection apiUrl={this.apiUrl} userId={this.props.userId} setCharacterId={this.setCharacterId.bind(this)} />
                {this.state.openPortDetails ? <PortLocationActions portDetails={this.state.portDetails} userId={this.props.userId} openPortDetails={this.openPortDetails.bind(this)} updatePlayerPosition={this.updatePlayerPosition.bind(this)}/> : null}
                {this.state.openAdventureDetails ? <AdeventureActions updateAdventure={this.updateAdventure.bind(this)} adventureDetails={this.state.adventureDetails} userId={this.props.userId} characterId={this.state.characterId} openAdventureDetails={this.openAdventureDetails.bind(this)} adventureAgainAt={this.state.canAdventureAgainAt} adventureLogs={this.state.adventureLogs} /> : null}
              </div>
              <div className="col-md-4">
                <Map 
                  apiUrl={this.apiUrl}
                  userId={this.props.userId}
                  updatePort={this.updatePort.bind(this)}
                  position={this.state.position}
                  updatePlayerPosition={this.updatePlayerPosition.bind(this)}
                  openPortDetails={this.openPortDetails.bind(this)}
                  openAdventureDetails={this.openAdventureDetails.bind(this)}
                  updateAdventure={this.updateAdventure.bind(this)}
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
const player    = document.head.querySelector('meta[name="player"]');
const character = document.head.querySelector('meta[name="character"]');

if (game !== null) {
  ReactDOM.render(
      <Game userId={parseInt(player.content)} characterId={character.content}/>,
      game
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