import 'reflect-metadata'

import _ from 'lodash';
window._ = _;

try {
    window.Popper = require('popper.js').default;
} catch (e) {
}
