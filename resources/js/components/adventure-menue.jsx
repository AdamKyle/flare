import React from 'react';
import ReactDOM from 'react-dom';
import AdventureMenu from './game/components/menu/adventure-menu';

/**
 * Register the adventure menu.
 *
 * The adventure menu shows in the game section and anywhere, where the
 * menu for the player exists.
 *
 * This will turn green and show an additional sub menu when the adventure
 * completes.
 */

const adventureMenu = document.getElementById('adventure-menu');
const player = document.head.querySelector('meta[name="player"]');

if (adventureMenu !== null) {
  ReactDOM.render(
    <AdventureMenu userId={parseInt(player.content)}/>,
    adventureMenu
  );
}
