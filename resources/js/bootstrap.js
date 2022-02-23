import Alpine from 'alpinejs'
import Echo from "laravel-echo"

window._ = require('lodash');

try {
  window.Popper = require('popper.js').default;
} catch (e) {
}

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

let token = document.head.querySelector('meta[name="csrf-token"]');

axios.interceptors.request.use(function (config) {
  config.headers = {
    Accept: 'application/json',
    'X-CSRF-TOKEN': token.content,
    'X-Requested-With': 'XMLHttpRequest'
  };

  return config;
}, function (error) {
  return Promise.reject(error);
});

/**
 * Alpine Set up:
 */
window.Alpine = Alpine;

Alpine.start();

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

window.Pusher = require('pusher-js');

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: process.env.MIX_PUSHER_APP_KEY,
  wsHost: window.location.hostname,
  wsPort: 6001,
  wssPort: 443,
  disableStats: true,
  enabledTransports: ['ws', 'wss'],
  namespace: 'App',
  auth: {
    headers: {
      'X-CSRF-TOKEN': token.content
    }
  }
});
