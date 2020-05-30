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
      portList: [],
      secondsRemaining: 10,
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
        portList: result.data.port_details !== null ? result.data.port_details.port_list : [],
        timeRemaining: result.data.timeout !== null ? result.data.timeout : null,
        isDead: result.data.is_dead,
      });
    });

    this.echo.listen('Game.Maps.Adventure.Events.ShowTimeOutEvent', (event) => {
      this.setState({
        canMove: event.canMove,
        showMessage: false,
        secondsRemaining: event.forLength !== 0 ? (event.forLength * 60) : 10,
        timeRemaining: event.canMove ? null : this.state.timeRemaining,
      });
    });

    this.isDead.listen('Game.Battle.Events.CharacterIsDeadBroadcastEvent', (event) => {
      this.setState({
        isDead: event.isDead
      });
    });
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

    if (y < 32) {
      return getServerMessage('cannot_move_up');
    }

    if (x < 16) {
      return getServerMessage('cannot_move_left');
    }

    if (y > 1984) {
      return getServerMessage('cannot_move_down');
    }

    if (x > 1984) {
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
            this.setState({
              currentPort: result.data.hasOwnProperty('current_port') ? result.data.current_port : null,
              portList: result.data.hasOwnProperty('port_list') ? result.data.port_list : [],
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
               bounds={{top: -1648, left: -464, right: this.state.rightBounds, bottom: this.state.bottomBounds}}
               handle=".handle"
               defaultPosition={{x: 0, y: 0}}
               grid={[16, 16]}
               scale={1}
               onStart={this.handleStart}
               onDrag={this.handleDrag.bind(this)}
               onStop={this.handleStop}
            >
            <div>
              <div className="handle game-map" style={{backgroundImage: `url(${this.state.mapUrl})`, width: 2000, height: 2000}}>
                {this.renderLocations()}
                <div className="map-x-pin" style={this.playerIcon()}></div>
              </div>
            </div>
           </Draggable>
         </div>
         <div className="character-position mt-2">
          <p>Character X/Y: {this.state.characterPosition.x}/{this.state.characterPosition.y}</p>
         </div>
         <hr />
         {this.state.currentPort !== null 
          ? 
          <div className="clear-fix mb-2">
            <SetSail 
              characterIsDead={this.state.isDead}
              currentPort={this.state.currentPort} 
              portList={this.state.portList} 
              characterId={this.state.characterId} 
              updatePlayerPosition={this.updatePlayerPosition.bind(this)}
              canSetSail={this.state.canMove}
            />
          </div>
          : null
         }
         <div className="clear-fix">
           <button type="button" className="float-left btn btn-primary mr-2" data-direction="north" disabled={this.state.isDead} onClick={this.move.bind(this)}>North</button>
           <button type="button" className="float-left btn btn-primary mr-2" data-direction="south" disabled={this.state.isDead} onClick={this.move.bind(this)}>South</button>
           <button type="button" className="float-left btn btn-primary mr-2" data-direction="east" disabled={this.state.isDead} onClick={this.move.bind(this)}>East</button>
           <button type="button" className="float-left btn btn-primary mr-2" data-direction="west" disabled={this.state.isDead} onClick={this.move.bind(this)}>West</button>
           <TimeOutBar
              userId={this.props.userId}
              eventName='Game.Maps.Adventure.Events.ShowTimeOutEvent'
              channel={'show-timeout-move-'}
              cssClass={'character-map-timeout'}
              readyCssClass={'character-map-ready float-left'}
              forSeconds={this.state.secondsRemaining}
              timeRemaining={this.state.timeRemaining}
            />
            {this.state.isDead ? <span className="text-danger revive">You must revive.</span> : null}
         </div>
        </div>

        <LocationInfoModal show={this.state.showLocationInfo} onClose={this.closeLocationDetails.bind(this)} location={this.state.location} />
      </div>
    )
  }
}
