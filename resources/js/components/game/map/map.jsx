import React                              from 'react';
import Draggable                          from 'react-draggable';
import {
  OverlayTrigger,
  Tooltip
}                                         from 'react-bootstrap';
import {getServerMessage}                 from '../helpers/server_message';
import {getNewXPosition, getNewYPosition} from './helpers/map_position';
import LocationInfoModal                  from '../components/location-info-modal';
import TimeOutBar                         from '../timeout/timeout-bar';
import SetSail                            from './components/set-sail';

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
      mapUrl: null,
      bottomBounds: 0,
      rightBounds: 0,
      isLoading: true,
      characterId: 0,
      showCharacterInfo: false,
      showLocationInfo: false,
      canMove: true,
      showMessage: false,
      locations: null,
      location: null,
      currentPort: null,
      adventures: [],
      portList: [],
      adventureLogs: [],
      timeRemaining: null,
      isDead: false,
    }

    this.echo = Echo.private('show-timeout-move-' + this.props.userId);
    this.isDead = Echo.private('character-is-dead-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/map/' + this.props.userId).then((result) => {
      this.setState({
        mapUrl: result.data.map_url,
        controlledPosition: {
          x: getNewXPosition(result.data.character_map.character_position_x, result.data.character_map.position_x),
          y:  getNewYPosition(result.data.character_map.character_position_y, result.data.character_map.position_y),
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
      }, () => {
        this.props.updatePort({
          currentPort: this.state.currentPort,
          portList: this.state.portList,
          characterId: this.state.characterId,
          characterIsDead: this.state.isDead,
          canMove: this.state.canMove,
        });

        this.props.updateAdventure(this.state.adventures, this.state.adventureLogs);
      });
    });

    this.echo.listen('Game.Maps.Adventure.Events.ShowTimeOutEvent', (event) => {
      this.setState({
        canMove: event.canMove,
        showMessage: false,
        timeRemaining: event.forLength !== 0 ? (event.forLength * 60) : 10,
      }, () => {
        this.props.updatePort({
          currentPort: this.state.currentPort,
          portList: this.state.portList,
          characterId: this.state.characterId,
          characterIsDead: this.state.isDead,
          canMove: event.canMove,
        });
      });
    });

    this.isDead.listen('Game.Battle.Events.CharacterIsDeadBroadcastEvent', (event) => {
      this.setState({
        isDead: event.isDead
      });
    });
  }

  componentDidUpdate(prevProps) {
    if (!_.isEmpty(this.props.position)) {
      this.updatePlayerPosition(this.props.position);
    }
  }

  handleDrag(e, position) {
    const {x, y}     = position;
    const yBounds    = Math.sign(position.y);
    const xBounds    = Math.sign(position.x);
    let bottomBounds = this.state.bottomBounds;
    let rightBounds  = this.state.rightBounds;

    if (yBounds === -1) {
      bottomBounds += Math.abs(yBounds);
    } else {
      bottomBounds = 0;
    }

    if (xBounds === -1) {
      rightBounds += Math.abs(xBounds);
    } else {
      rightBounds = 0;
    }

    this.setState({
      controlledPosition: {x, y},
      bottomBounds: bottomBounds,
    });
  }

  playerIcon() {
    return {
      top: this.state.characterPosition.y + 'px',
      left: this.state.characterPosition.x + 'px',
    }
  }

  updatePlayerPosition(position) {
    const characterX = position.character_position_x;
    const characterY = position.character_position_y;
    const mapX       = position.position_x;
    const mapY       = position.position_y;

    this.setState({
      characterPosition: {x: characterX, y: characterY},
      controlledPosition: {x: getNewXPosition(characterX, mapX), y: getNewYPosition(characterY, mapY)},
    }, () => {
      this.props.updatePlayerPosition({});
    });
  }

  move(e) {
    if (!this.state.canMove) {
      return getServerMessage('cant_move');
    }

    if (this.state.isDead) {
      return getServerMessage('dead_character');
    }

    const movement  = e.target.getAttribute('data-direction');
    let x           = this.state.characterPosition.x;
    let y           = this.state.characterPosition.y;
    let mapX        = 0;
    let mapY        = 0;

    switch (movement) {
        case 'north':
          y = y - 16;
          break;
        case 'south':
          y = y + 16;
          break;
        case 'east':
          x = x + 16;
          break;
        case 'west':
          x = x - 16;
          break;
        default:
          break;
    }

    if (y < 16) {
      return getServerMessage('cannot_move_up');
    }

    if (x < 0) {
      return getServerMessage('cannot_move_left');
    }

    if (y > 496) {
      return getServerMessage('cannot_move_down');
    }

    if (x > 496) {
      return getServerMessage('cannot_move_right');
    }

    axios.get('/api/is-water/' + this.state.characterId, {
      params: {
        character_position_x: x,
        character_position_y: y,
      }
    })
      .then((result) => {
        // If we're not water:
        this.setState({
          characterPosition: {x, y},
          controlledPosition: {x: getNewXPosition(x, this.state.controlledPosition.x), y: getNewYPosition(y, this.state.controlledPosition.y)},
        }, () => {
          axios.post('/api/move/' + this.state.characterId, {
            position_x: this.state.controlledPosition.x,
            position_y: this.state.controlledPosition.y,
            character_position_x: this.state.characterPosition.x,
            character_position_y: this.state.characterPosition.y,
          }).then((result) => {
            console.log(result);
            this.setState({
              currentPort: result.data.port_details.hasOwnProperty('current_port') ? result.data.port_details.current_port : null,
              portList: result.data.port_details.hasOwnProperty('port_list') ? result.data.port_details.port_list : [],
              adventures: result.data.adventure_details,
            }, () => {
              this.props.updatePort({
                currentPort: this.state.currentPort,
                portList: this.state.portList,
                characterId: this.state.characterId,
                characterIsDead: this.state.isDead,
                canMove: this.state.canMove,
              });

              this.props.updateAdventure(this.state.adventures);

              if (this.state.currentPort == null) {
                this.props.openPortDetails(false);
              }

              if (_.isEmpty(this.state.adventures)) {
                this.props.openAdventureDetails(false);
              }
            });
          });
        });
      })
     .catch((error) => {
       this.setState({
         characterPosition: {x: this.state.characterPosition.x, y: this.state.characterPosition.y},
       });

       // If we are:
       return getServerMessage('cannot_walk_on_water');
     });
  }

  openLocationDetails(e) {
    const location = this.state.locations.filter(l => l.id === parseInt(event.target.getAttribute('data-location-id')))[0];

    this.setState({
      showLocationInfo: true,
      location: location,
    });
  }

  closeLocationDetails() {
    this.setState({
      showLocationInfo: false,
      location: null,
    });
  }

  renderLocations() {
    return this.state.locations.map((location) => {
      if (location.is_port) {
        return (
          <div
            key={location.id}
            data-location-id={location.id}
            className="port-x-pin"
            style={{top: location.y, left: location.x}}
            onClick={this.openLocationDetails.bind(this)}>
          </div>
        );
      } else {
        return (
          <div
            key={location.id}
            data-location-id={location.id}
            className="location-x-pin"
            style={{top: location.y, left: location.x}}
            onClick={this.openLocationDetails.bind(this)}>
          </div>
        );
      }

    });
  }

  openPortDetails() {
    this.props.openPortDetails(true);
  }

  openAdventureDetails() {
    this.props.openAdventureDetails(true);
  }

  render() {
    if (this.state.isLoading) {
      return 'Please wait ...';
    }

    return (
      <div className="card mb-4">
        <div className="card-body">
          <div className="map-body">
            <Draggable
               position={this.state.controlledPosition}
               bounds={{top: -160, left: -25, right: this.state.rightBounds, bottom: this.state.bottomBounds}}
               handle=".handle"
               defaultPosition={{x: 0, y: 0}}
               grid={[16, 16]}
               scale={1}
               onStart={this.handleStart}
               onDrag={this.handleDrag.bind(this)}
               onStop={this.handleStop}
            >
            <div>
              <div className="handle game-map" style={{backgroundImage: `url(${this.state.mapUrl})`, width: 500, height: 500}}>
                {this.renderLocations()}
                <div className="map-x-pin" style={this.playerIcon()}></div>
              </div>
            </div>
           </Draggable>
         </div>
         <div className="character-position mt-2">
          <div className="mb-2 mt-2 clearfix">
            <p className="float-left">Character X/Y: {this.state.characterPosition.x}/{this.state.characterPosition.y}</p>
            { this.state.currentPort !== null ? <button type="button" className="float-right btn btn-success mr-2 btn-sm" onClick={this.openPortDetails.bind(this)}>Set Sail</button> : null}
            { !_.isEmpty(this.state.adventures) ? <button type="button" className="float-right btn btn-success mr-2 btn-sm" onClick={this.openAdventureDetails.bind(this)}>Adventure</button> : null}
          </div>
         </div>
         <hr />
         <div className="mb-2 mt-2">
          {this.state.isDead ? <span className="text-danger revive">You must revive.</span> : null}
         </div>
         <div className="clearfix">
           <button type="button" className="float-left btn btn-primary mr-2 btn-sm" data-direction="north" disabled={this.state.isDead} onClick={this.move.bind(this)}>North</button>
           <button type="button" className="float-left btn btn-primary mr-2 btn-sm" data-direction="south" disabled={this.state.isDead} onClick={this.move.bind(this)}>South</button>
           <button type="button" className="float-left btn btn-primary mr-2 btn-sm" data-direction="east" disabled={this.state.isDead} onClick={this.move.bind(this)}>East</button>
           <button type="button" className="float-left btn btn-primary mr-2 btn-sm" data-direction="west" disabled={this.state.isDead} onClick={this.move.bind(this)}>West</button>
           <TimeOutBar
              eventClass={'Game.Maps.Adventure.Events.ShowTimeOutEvent'}
              channel={'show-timeout-move-' + this.props.userId}
              cssClass={'character-map-timeout'}
              readyCssClass={'character-map-ready float-left'}
              timeRemaining={this.state.timeRemaining}
            />
         </div>
        </div>

        <LocationInfoModal show={this.state.showLocationInfo} onClose={this.closeLocationDetails.bind(this)} location={this.state.location} />
      </div>
    )
  }
}
