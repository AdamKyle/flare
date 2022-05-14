/**
 * Next, we will create a fresh React component instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

require('./bootstrap');

/**
 * Load the game.
 *
 * This will grab the user and the character id from the
 * meta tags that are fill when logged in.
 */
require('./game/game-launcher');

/**
 * When the administrator is logged in, load their chat.
 */
require('./game/admin/admin-chat');

/**
 * When the administrator is managing the information help docs.
 */
require('./game/admin/info-management/info-management-init');

