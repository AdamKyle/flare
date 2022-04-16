import React from 'react';
import ReactDOM from 'react-dom';
import AdventureMenu from './game/components/menu/adventure-menu';
import KingdomsMenu from "./game/components/menu/kingdoms-menu";

/**
 * Register the kingdom menu.
 *
 * The adventure menu shows in the game section and anywhere, where the
 * menu for the player exists.
 *
 * This will turn red when there are new unopened messages.
 */

const adventureMenu = document.getElementById('kingdom-menu');
const player = document.head.querySelector('meta[name="player"]');

if (adventureMenu !== null) {
  ReactDOM.render(
    <KingdomsMenu userId={parseInt(player.content)}/>,
    adventureMenu
  );
}
