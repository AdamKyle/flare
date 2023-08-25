/**
 * Boot strap the application.
 */
require('./bootstrap');

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

/**
 * Renders the event calendar for the admin to manage.
 */
require('./game/admin/calendar');
