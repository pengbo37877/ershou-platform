let mix = require('laravel-mix');

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
// webpack.config.js
mix.config.uglify.uglifyOptions = {
    
    compress: {
        warnings: false,
        // drop_console: true,//console
        // pure_funcs: ['console.log']//移除console
    },
    output: {
        comments: false
    },
    mangle: {
        safari10: true
    },
    entry: {
        babelPolyfill: "resources/assets/js/app.js"
    }
}

mix.js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .version();
