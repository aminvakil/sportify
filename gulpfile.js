var elixir = require('laravel-elixir');

config.assetsPath = '.';
config.publicPath = '.';

elixir(function(mix) {

    mix
    .sass([

        'bootstrap.scss',
        '../node_modules/owl.carousel/dist/assets/owl.carousel.min.css',
        '../node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css',
        '../node_modules/chosen-js/chosen.css',
        'flag-icon.scss',
        'style.scss'

        ],'web/css/style.css')
    .scripts([
        '../node_modules/jquery/dist/jquery.min.js',
        '../node_modules/bootstrap-sass/assets/javascripts/bootstrap.min.js',
        '../node_modules/owl.carousel/dist/owl.carousel.min.js',
        '../node_modules/chosen-js/chosen.jquery.js',
        '../node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
        '../web/js/script.js',
        ],'web/js/all-scripts.js');
});