import React from 'react';
import ReactDOM from 'react-dom';
import ServerTimeClock from "./game/components/nav-bar/server-clock/server-time-clock";

/**
 * Refresh
 *
 * This is used for when the player game screen (or any screen the player is on) needs
 * to be refreshed. The refresh can happen for any reason, but the main reason is if the player is
 * banned. We need to refresh to kick them out.
 */

const player = document.head.querySelector('meta[name="player"]');
const clock  = document.getElementById('server-clock');

if (refresh !== null) {
  ReactDOM.render(
    <ServerTimeClock userId={parseInt(player.content)} />,
    clock
  );
}
