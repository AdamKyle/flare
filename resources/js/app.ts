/**
 * Bootstrap the application.
 */
import './bootstrap';

/**
 * Only shows when the player has the guide quest enabled.
 */
import './game/sections/guide-quests/guide-quests-init';

/**
 * Load the game.
 *
 * This will grab the user and the character id from the
 * meta tags that are fill when logged in.
 */
import './game/game-launcher';

/**
 * Load the rank fight tops chart and list.
 */
import './game/tops/rank-fight-tops-component';

/**
 * Load the event calendar for the player.
 */
import './individual-components/player-components/event-calendar/calendar-component';
