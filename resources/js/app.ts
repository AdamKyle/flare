/**
 * Boot strap the application.
 */
require('./bootstrap');

/**
 * Only shows when the player has the guide quest enabled.
 */
require('./game/sections/guide-quests/guide-quests-init');

/**
 * Load the game.
 *
 * This will grab the user and the character id from the
 * meta tags that are fill when logged in.
 */
require('./game/game-launcher');

/**
 * Load the rank fight tops chart and list.
 */
require('./game/tops/rank-fight-tops-component');

/**
 * Load the event calendar for the player.
 */
require('./game/event-calendar/calendar-component');

