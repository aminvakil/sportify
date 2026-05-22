'use strict';

var gulp = require('gulp');
var concat = require('gulp-concat');
var sass = require('gulp-sass')(require('sass'));

var cssSources = [
    'sass/bootstrap.scss',
    'node_modules/owl.carousel/dist/assets/owl.carousel.min.css',
    'node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css',
    'node_modules/chosen-js/chosen.css',
    'sass/flag-icon.scss',
    'sass/style.scss'
];

var jsSources = [
    'node_modules/jquery/dist/jquery.min.js',
    'node_modules/bootstrap-sass/assets/javascripts/bootstrap.min.js',
    'node_modules/owl.carousel/dist/owl.carousel.min.js',
    'node_modules/chosen-js/chosen.jquery.js',
    'node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
    'web/js/script.js'
];

function styles() {
    return gulp.src(cssSources)
        .pipe(sass().on('error', sass.logError))
        .pipe(concat('style.css'))
        .pipe(gulp.dest('web/css'));
}

function scripts() {
    return gulp.src(jsSources)
        .pipe(concat('all-scripts.js'))
        .pipe(gulp.dest('web/js'));
}

exports.styles = styles;
exports.scripts = scripts;
exports.default = gulp.parallel(styles, scripts);
