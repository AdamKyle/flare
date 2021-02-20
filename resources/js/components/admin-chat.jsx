import React from 'react';
import ReactDOM from 'react-dom';
import Chat from './game/messages/chat';

/**
 * Register the Admin chat.
 * 
 * This is the same chat box that players would see, 
 * how ever this chat will only be registered on the 
 * admin side as opposed to in game.
 */

const adminChat = document.getElementById('admin-chat');
const player    = document.head.querySelector('meta[name="player"]');

if (adminChat !== null) {
    ReactDOM.render(
      <Chat userId={parseInt(player.content)} />,
      adminChat
  );
}