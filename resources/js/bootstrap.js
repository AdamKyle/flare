import 'reflect-metadata'

import _ from 'lodash';
window._ = _;

try {
    window.Popper = require('popper.js').default;
} catch (e) {
}

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Pusher from 'pusher-js';
window.Pusher = Pusher;
