const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.react('resources/js/app.js', 'public/js')
   .react('resources/js/helpers/market-board.js', 'public/js')
   .react('resources/js/helpers/kingdom-unit-movement.js', 'public/js')
   .react('resources/js/helpers/admin-chat-messages.js', 'public/js')
   .react('resources/js/helpers/admin-site-stats-components.js', 'public/js')
   .react('resources/js/helpers/character-boons.js', 'public/js')
   .react('resources/js/helpers/admin-statistics.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   .extract([
     'react',
     'react-dom',
     'react-bootstrap',
     'jquery',
     'lodash',
     'moment',
     'bootstrap',
     'axios',
   ])
   .sourceMaps()
   .version()
   .sourceMaps()
   .browserSync('127.0.0.1:8000');
