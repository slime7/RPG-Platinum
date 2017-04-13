'use strict';

var gulp = require('gulp');
var concat = require('gulp-concat');
var minifyCss = require('gulp-clean-css');
var del = require('del');
var uglify = require('gulp-uglify');

gulp.task('clean', function (cb) {
  del('asset/dist/*', cb);
});

gulp.task('minify-css', function() {
  return gulp.src('asset/css/*.css')
    .pipe(minifyCss({compatibility: 'ie8'}))
    .pipe(concat('rpgplatinum.min.css'))
    .pipe(gulp.dest('asset/dist/'));
});

gulp.task('concat-uglify-js', function () {
  return gulp.src([
      'asset/js/app.js',
      'asset/js/controllers/*.js'
    ])
    .pipe(concat('rpgplatinum.min.js'))
    .pipe(uglify())
    .pipe(gulp.dest('asset/dist/'));
});

gulp.task('build', ['clean', 'minify-css', 'concat-uglify-js']);
