import React from 'react';
import {movePlayer} from '../helpers/movement';
import TimeOutBar from '../../timeout/timeout-bar';

export default class MapMovementActions extends React.Component {

  constructor(props) {
    super(props);

  }

  move(e) {
    const coordinates = movePlayer(
      this.props.characterPosition.x,
      this.props.characterPosition.y,
      e.target.getAttribute('data-direction')
    );

    this.props.move(coordinates);
  }

  render() {
    return (
      <>
        <div className="mb-2 mt-2">
          {
            this.props.isDead ?
              <span className="text-danger revive">You must revive.</span>
              : null
          }
        </div>
        <div className="clearfix">
          {
            this.props.isAdventuring ?
              <div className="alert alert-warning" role="alert">
                You are currently adventuring and cannot move or set sail.
              </div>
              : null
          }

          <button type="button" className="float-left btn btn-primary mr-2 btn-sm" data-direction="north"
                  disabled={this.props.disableMapButtons()} onClick={this.move.bind(this)}>North
          </button>
          <button type="button" className="float-left btn btn-primary mr-2 btn-sm" data-direction="south"
                  disabled={this.props.disableMapButtons()} onClick={this.move.bind(this)}>South
          </button>
          <button type="button" className="float-left btn btn-primary mr-2 btn-sm" data-direction="east"
                  disabled={this.props.disableMapButtons()} onClick={this.move.bind(this)}>East
          </button>
          <button type="button" className="float-left btn btn-primary mr-2 btn-sm" data-direction="west"
                  disabled={this.props.disableMapButtons()} onClick={this.move.bind(this)}>West
          </button>
          <button type="button" className="float-left btn btn-primary btn-sm mr-2 " data-direction="traverse"
                  disabled={this.props.disableMapButtons()} onClick={this.props.openTraverse}>Traverse
          </button>
          <TimeOutBar
            eventClass={'Game.Maps.Events.ShowTimeOutEvent'}
            channel={'show-timeout-move-' + this.props.userId}
            cssClass={'character-map-timeout'}
            readyCssClass={'character-map-ready float-left'}
            timeRemaining={this.props.timeRemaining}
          />
        </div>
      </>
    )
  }
}
