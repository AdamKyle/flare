import React              from 'react';
import Draggable          from 'react-draggable';
import {getServerMessage} from '../helpers/server_message';

export default class Map extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      controlledPosition: {
        x: 0, y: 0
      },
      characterPosition: {
        x: 32, y: 32
      },
      mapUrl: null,
      bottomBounds: 0,
      rightBounds: 0,
    }
  }

  componentDidMount() {
    axios.get('/api/map').then((result) => {
      this.setState({
        mapUrl: result.data.map_url,
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

    if (x < 32) {
      return getServerMessage('cannot_move_left');
    }

    if (y > 1248) {
      return getServerMessage('cannot_move_down');
    }

    if (x > 1216) {
      return getServerMessage('cannot_move_right');
    }

    if (y >= 336) {
      mapY = -304;
    }

    if (x >= 848) {
      mapX = -368;
    }

    if (y >= 640) {
      mapY = -608;
    }

    if (y >= 944) {
      mapY = -900;
    }

    this.setState({
      characterPosition: {x, y},
      controlledPosition: {x: mapX, y: mapY},
    });
  }

  render() {
    return (
      <div className="card mb-4">
        <div className="card-body">
          <div className="map-body">
            <Draggable
               position={this.state.controlledPosition}
               bounds={{top: -900, left: -368, right: this.state.rightBounds, bottom: this.state.bottomBounds}}
               handle=".handle"
               defaultPosition={{x: 0, y: 0}}
               grid={[16, 16]}
               scale={1}
               onStart={this.handleStart}
               onDrag={this.handleDrag.bind(this)}
               onStop={this.handleStop}
            >
            <div>
              <div className="handle game-map" style={{backgroundImage: `url(${this.state.mapUrl})`, width: 1250, height: 1250}}>
                <div className="map-x-pin" style={this.playerIcon()}></div>
              </div>
            </div>
           </Draggable>
         </div>
         <hr />
         <button type="button" className="btn btn-primary mr-2" data-direction="north" onClick={this.move.bind(this)}>North</button>
         <button type="button" className="btn btn-primary mr-2" data-direction="south" onClick={this.move.bind(this)}>South</button>
         <button type="button" className="btn btn-primary mr-2" data-direction="east" onClick={this.move.bind(this)}>East</button>
         <button type="button" className="btn btn-primary mr-2" data-direction="west" onClick={this.move.bind(this)}>West</button>
        </div>
      </div>
    )
  }
}
