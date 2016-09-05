/** ESLint Configuration **/
/* eslint-env node*/
/* eslint quotes: [1, "single"]*/
'use strict';
var gulp         = require('gulp');
var autoprefixer = require('gulp-autoprefixer');
var concat       = require('gulp-concat');
var gutil        = require('gulp-util');
var rename       = require('gulp-rename');
var sass         = require('gulp-sass');
var sourcemaps   = require('gulp-sourcemaps');
var uglify       = require('gulp-uglify');

function handleError(err) {
    var displayErr = gutil.colors.red("\n" + err.message + "\n" + err.file);
    gutil.log(displayErr);
    return this.emit('end');
}

gulp.task('sass', function(){
    return gulp.src('scss/layout.scss')
    .pipe(sourcemaps.init())
    .pipe(concat('layout.css'))
    .pipe(sass({
        includePaths: './node_modules/bootstrap-sass/assets/stylesheets/',
        outputStyle: 'compressed',
        sourcemap: true
    }).on('error', handleError))
    .pipe(autoprefixer({
      browsers: ['last 2 versions', 'ie >= 9', 'and_chr >= 2.3']
    }))
    .pipe(rename({
        extname: '.min.css'
    }))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('./dist/'));
});

gulp.task('javascript', function() {
    return gulp.src(
            'js/*.js'
        )
        .pipe(sourcemaps.init())
        .pipe(uglify().on('error', handleError))
        .pipe(concat('script.js'))
        .pipe(rename({
            extname: '.min.js'
    }))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('./dist/'));
});

gulp.task('javascript-dependencies', function() {
    return gulp.src([
        './node_modules/jquery/dist/jquery.js',
        './node_modules/bootstrap-sass/assets/javascripts/bootstrap.js'
        ])
    .pipe(sourcemaps.init())
    .pipe(uglify().on('error', handleError))
    .pipe(concat('dependencies.min.js'))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('./dist/'));
});

gulp.task('default', ['javascript-dependencies', 'javascript', 'sass'], function(){
    gulp.watch('./scss/**/*.scss', ['sass']);
    gulp.watch('./js/**/*.js', ['javascript']);
});