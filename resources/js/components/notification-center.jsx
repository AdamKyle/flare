import React from 'react';
import ReactDOM from 'react-dom';
import NotificationCenter from './game/components/nav-bar/notification-center';

/**
 * The notification center shows in the top nav bar.
 * 
 * This allows us to show the player notifications. such as the adventure
 * completion or failure notifications.
 */

const notificationCenter = document.getElementById('notification-center');
const player             = document.head.querySelector('meta[name="player"]');

if (notificationCenter !== null) {
  ReactDOM.render(
      <NotificationCenter userId={parseInt(player.content)} />,
      notificationCenter
  );
}