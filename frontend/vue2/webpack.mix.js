const mix = require('laravel-mix')

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

mix.setPublicPath('../../public')

mix.js('./js/app.js', 'js')
    .vue()
    .sass('./sass/app.scss', 'css')
    .browserSync({
        proxy: 'localhost',
        host: 'localhost',
        open: 'external',
        files: ['js/**/*.vue', 'js/**/*.js', 'sass/**/*.scss'],
    })
    .version()
