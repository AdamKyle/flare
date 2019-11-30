import React                              from 'react';
import Draggable                          from 'react-draggable';
import {getServerMessage}                 from '../helpers/server_message';
import {getNewXPosition, getNewYPosition} from './helpers/map_position';
import CharacterInfoModal                 from '../components/character-info-modal';
import TimeOutBar                         from '../timeout/timeout-bar';

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
      canMove: true,
      showMessage: false,
    }

    this.echo = Echo.private('show-timeout-move-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/map/' + this.props.userId).then((result) => {
      console.log(result.data);
      this.setState({
        mapUrl: result.data.map_url,
        controlledPosition: {
          x: result.data.character_map.position_x,
          y: result.data.character_map.position_y
        },
        characterPosition: {
          x: result.data.character_map.character_position_x,
          y: result.data.character_map.character_position_y,
        },
        characterId: result.data.character_id,
        isLoading: false,
        canMove: result.data.can_move,
        showMessage: result.data.show_message,
      });
    });

    this.echo.listen('Game.Maps.Adventure.Events.ShowTimeOutEvent', (event) => {
      this.setState({
        canMove: event.canMove,
        showMessage: false,
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

  move(e) {

    if (!this.state.canMove) {
      return getServerMessage('cant_move');
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

    this.setState({
      characterPosition: {x, y},
      controlledPosition: {x: getNewXPosition(x, this.state.controlledPosition.x), y: getNewYPosition(y, this.state.controlledPosition.y)},
    }, () => {
      axios.post('/api/move/' + this.state.characterId, {
        position_x: this.state.controlledPosition.x,
        position_y: this.state.controlledPosition.y,
        character_position_x: this.state.characterPosition.x,
        character_position_y: this.state.characterPosition.y,
      });
    });
  }

  showCharacterInfo() {
    this.setState({
      showCharacterInfo: true,
    });
  }

  hideCharacterInfo() {
    this.setState({
      showCharacterInfo: false,
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
               bounds={{top: -1648, left: -1120, right: this.state.rightBounds, bottom: this.state.bottomBounds}}
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
                <div className="map-x-pin" style={this.playerIcon()} onClick={this.showCharacterInfo.bind(this)}></div>
              </div>
            </div>
           </Draggable>
         </div>
         <hr />
         <div className="clear-fix">
           <button type="button" className="float-left btn btn-primary mr-2" data-direction="north" onClick={this.move.bind(this)}>North</button>
           <button type="button" className="float-left btn btn-primary mr-2" data-direction="south" onClick={this.move.bind(this)}>South</button>
           <button type="button" className="float-left btn btn-primary mr-2" data-direction="east" onClick={this.move.bind(this)}>East</button>
           <button type="button" className="float-left btn btn-primary mr-2" data-direction="west" onClick={this.move.bind(this)}>West</button>
           {this.state.showMessage
            ? 'Almost Ready!'
            : <TimeOutBar userId={this.props.userId} eventName='Game.Maps.Adventure.Events.ShowTimeOutEvent' channel={'show-timeout-move-'} cssClass={'character-map-timeout'}/>
           }
         </div>
        </div>

        <CharacterInfoModal show={this.state.showCharacterInfo} onClose={this.hideCharacterInfo.bind(this)} characterId={this.state.characterId} />
      </div>
    )
  }
}
