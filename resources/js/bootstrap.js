import 'remixicon/fonts/remixicon.css'

import Echo from "laravel-echo"
import 'reflect-metadata'

import _ from 'lodash';
window._ = _;

try {
    window.Popper = require('popper.js').default;
} catch (e) {
}

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

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
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: window.location.hostname,
    wsPort: 6001,
    wssPort: 6001,
    enabledTransports: ['ws', 'wss'],
    namespace: 'App',
    auth: {
        headers: {
            'X-CSRF-TOKEN': token.content
        }
    }
});
