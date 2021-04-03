import React from 'react';
import ReactDOM from 'react-dom';
import Game from './game/game';

/**
 * Mount the game.
 *
 * The game is mounted when the player logs in.
 *
 * This includes the map, the chat, the actions, the character top bar,
 * everything that they see when they login.
 */

const game = document.getElementById('game');
const player = document.head.querySelector('meta[name="player"]');
const character = document.head.querySelector('meta[name="character"]');

if (game !== null) {
  ReactDOM.render(
    <Game userId={parseInt(player.content)} characterId={character.content}/>,
    game
  );
}
