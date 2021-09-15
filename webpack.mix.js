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

mix.react('resources/js/app.js', 'public/js').extract()
   .react('resources/js/helpers/kingdom-unit-movement.js', 'public/js').extract()
   .react('resources/js/helpers/admin-chat-messages.js', 'public/js').extract()
   .react('resources/js/helpers/admin-site-stats-components.js', 'public/js').extract()
   .react('resources/js/helpers/character-boons.js', 'public/js').extract()
   .react('resources/js/helpers/admin-statistics.js', 'public/js').extract()
  .react('resources/js/helpers/character-inventory.js', 'public/js').extract()
   .sass('resources/sass/app.scss', 'public/css')
   .sourceMaps()
   .version()
   .browserSync('127.0.0.1:8000');
