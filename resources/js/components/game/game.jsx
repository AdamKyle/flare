import React from 'react';
import ReactDOM from 'react-dom';
import Chat from './messages/chat';

class Game extends React.Component {
  constructor(props) {
    super(props);

    this.apiUrl = window.location.protocol + '//' + window.location.host + '/api/';
  }

  render() {
    return (
      <div>
        <div className="row">
          <div className="col-md-9">
            <hr />
            <hr />
            <Chat apiUrl={this.apiUrl} userId={this.props.userId}/>
          </div>
          <div className="col-md-3">
          </div>
        </div>
        <hr />
      </div>
    )
  }
}

// Mount the app:
const game    = document.getElementById('game');
const player = document.head.querySelector('meta[name="player"]');

if (game !== null) {
  ReactDOM.render(
      <Game userId={parseInt(player.content)} />,
      game
  );
}
