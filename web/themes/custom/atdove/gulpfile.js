/**
 * Gulp File.
 *
 * To compile SCSS: gulp build-css
 * To compile JS: gulp build-js
 * To compile all: gulp build
 * To compile and watch SCSS and JS: gulp watch
 * To run sass-lint on SCSS: gulp lint-scss
 */

var gulp = require('gulp');
var sass = require('gulp-sass')(require('node-sass'));
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('gulp-autoprefixer');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
var sassLint = require('gulp-sass-lint');
var addsrc = require('gulp-add-src');

// Task: Compile CSS.
gulp.task('compile-css', function() {
  return gulp
    .src('./src/scss/main.scss')
    .pipe(sourcemaps.init())
    .pipe(
      sass({
        includePaths: ['./node_modules/bootstrap/scss'],
        errLogToConsole: true,
        outputStyle: 'compressed',
      }).on('error', sass.logError)
    )
    .pipe(
      autoprefixer({
        Browserslist: ['last 25 versions']
      })
    )
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('./dist/css'));
});

// Task: Compile Admin CSS.
// These are CSS overrides for the Adminimal admin theme.
// gulp.task('compile-admin-css', function() {
//   return gulp
//     .src('../../../modules/custom/oh_admin/scss/style-admin.scss')
//     .pipe(sourcemaps.init())
//     .pipe(
//       sass({
//         errLogToConsole: true,
//         outputStyle: 'compressed',
//       }).on('error', sass.logError)
//     )
//     .pipe(
//       autoprefixer({
//         Browserslist: ['last 25 versions']
//       })
//     )
//     .pipe(sourcemaps.write('.'))
//     // @TODO: Renaming to min is causing sourcemaps to be incorrect.
//     // The renaming is really just for clarity because the 'compressed'
//     // option passed above is what actually minifies.
//     // If we do resolve this, update overlake.libraries.yml to point to new file name.
//     // .pipe(rename({ suffix: '.min' }))
//     .pipe(gulp.dest('../../../modules/custom/oh_admin/build/css'));
// });

// Task: Lint SCSS files using sass-lint.
gulp.task('lint-scss', function() {
  return gulp
    .src(['./src/scss/**/*.scss'])
    .pipe(sassLint({
      options: {
        configFile: './.sass-lint.yml',
        formatter: 'stylish'
      },
    }))
    .pipe(sassLint.format())
    .pipe(sassLint.failOnError());
});

// Task: Build CSS and run sass-lint to notify of any errors (but not fix them).
gulp.task('build-css', gulp.series('compile-css', 'lint-scss'));

// Task: Build JS.
gulp.task('build-js', function() {
  // Global.js will be loaded on all pages in overlake.libraries.yml.
  // If we have JS that should not be loaded on all pages we should
  // minify it to a separate file and create a new Drupal library for it.
  // @TODO: Can we target everything in the /js directory without having to
  // explicitly write it out?
  return gulp
    .src([
      './src/js/global.js',
      './src/js/atdove-sso-login-block.js'
    ])
    .pipe(uglify())
    .pipe(gulp.dest('./dist/js'));
});

// Task: Build both CSS and JS.
gulp.task('build', gulp.series('build-css', 'build-js'));

// Task: Watch both CSS and JS.
gulp.task('watch', function() {
  gulp.watch('./src/scss/**/*.scss', gulp.series('build-css'));
  // gulp.watch('../../../modules/custom/oh_admin/scss/**/*.scss', gulp.series('build-css'));
  gulp.watch('./src/js/**/*.js', gulp.series('build-js'));
});

// Task: Default Task.
gulp.task('default', gulp.series('build-css', 'build-js', 'watch'));
