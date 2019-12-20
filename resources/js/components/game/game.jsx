import React               from 'react';
import ReactDOM            from 'react-dom';
import Chat                from './messages/chat';
import Actions             from './battle/actions';
import TimeOutBar          from './timeout/timeout-bar';
import Map                 from './map/map';
import CharacterInfoTopBar from './components/character-info-top-bar';
import Sidebar             from './sidebar/adventure/sidebar';

class Game extends React.Component {
  constructor(props) {
    super(props);

    this.apiUrl = window.location.protocol + '//' + window.location.host + '/api/';
  }

  render() {
    console.log(this.props);
    return (
      <div>
        <hr />
        <div className="row mb-4">
          <div className="col-md-10">
            <div className="row">
              <div className="col-md-12">
                <CharacterInfoTopBar apiUrl={this.apiUrl} characterId={this.props.characterId} userId={this.props.userId}/>
              </div>
            </div>
            <div className="row">
              <div className="col-md-12">
                <Map apiUrl={this.apiUrl} userId={this.props.userId} />
              </div>
            </div>
            <div className="row">
              <div className="col-md-12">
                <Actions apiUrl={this.apiUrl} userId={this.props.userId} />
              </div>
            </div>

          </div>
          <div className="col-md-2">
            <Sidebar characterId={this.props.characterId} />
          </div>
        </div>
        <div className="row">
          <div className="col-md-12">
            <Chat apiUrl={this.apiUrl} userId={this.props.userId}/>
          </div>
        </div>
        <hr />
      </div>
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
