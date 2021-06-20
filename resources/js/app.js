/**
 * First we will load all of this project's JavaScript dependencies which
 * includes React and other helpers. It's a great starting point while
 * building robust, powerful web applications using React + Laravel.
 */

require('./bootstrap');

require('../vendor/monster/theme/js/jquery.slimscroll.js');
require('../vendor/monster/theme/js/waves.js');
require('../vendor/monster/assets/plugins/sticky-kit-master/dist/sticky-kit.min.js');
require('../vendor/monster/theme/js/sidebarmenu.js');
require('../vendor/monster/theme/js/custom.js');

/**
 * Next, we will create a fresh React component instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

require('./components/game');
require('./components/admin-chat');
require('./components/adventure-menue');
require('./components/notification-center');
require('./components/refresh');
require('./components/marketboard/board');
require('./components/marketboard/listing/character-items');
