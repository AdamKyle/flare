/**
 * Next, we will create a fresh React component instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
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
 * When the administrator is logged in, load their chat.
 */
require('./game/admin/admin-chat');

/**
 * When the administrator visits their statistics dashboard.
 */
require('./game/admin/statistics-dashboard');

/**
 * When the administrator is managing the information help docs.
 */
require('./game/admin/info-management/info-management-init');

