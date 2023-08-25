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

const tailwindcss             = require('tailwindcss');
const postCssImport           = require("postcss-import");
const postCssNested           = require("postcss-nested");
const postcssCustomProperties = require("postcss-custom-properties");
const autoprefixer            = require("autoprefixer");

mix.webpackConfig({
  stats: {
    hash: true,
    version: true,
    timings: true,
    children: true,
    errors: true,
    errorDetails: true,
    warnings: true,
    chunks: true,
    modules: false,
    reasons: true,
    source: true,
    publicPath: true,
  },
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        loader: "ts-loader",
        exclude: /node_modules/
      }
    ]
  },
  resolve: {
    extensions: ["*", ".js", ".jsx", ".vue", ".ts", ".tsx"]
  }
}).ts('resources/js/app.ts', 'public/js').react().extract()
  .ts('resources/js/admin-app.ts', 'public/js').react().extract()
  .sass('resources/sass/app.scss', 'public/css')
  .postCss('resources/css/tailwind.css', 'public/css', [
    postCssImport(),
    require('tailwindcss/nesting')(require('postcss-nesting')),
    tailwindcss({ config: './tailwind.config.js' }),
    postCssNested(),
    postcssCustomProperties(),
    autoprefixer(),
  ])
  .combine(
    [
      "resources/vendor/theme/assets/js/script.js",
      "resources/vendor/theme/assets/js/extras.js",
      "resources/vendor/theme/assets/js/components/",
    ],
    "public/js/theme-script.js"
  )
  .combine(
    [
      "node_modules/@popperjs/core/dist/umd/popper.min.js",
      "node_modules/tippy.js/dist/tippy.umd.min.js",
    ],
    "public/js/theme-vendor.js"
  )
  .copy(
    [
      "node_modules/@glidejs/glide/dist/glide.min.js",
      "resources/vendor/theme/assets/js/dark-mode/dark-mode.js",
    ],
    "public/js/"
  )
  .version()
  .sourceMaps();
