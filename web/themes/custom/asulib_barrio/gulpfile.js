let gulp = require('gulp'),
  sass = require('gulp-sass')(require('sass')),
  sourcemaps = require('gulp-sourcemaps'),
  $ = require('gulp-load-plugins')(),
  cleanCss = require('gulp-clean-css'),
  rename = require('gulp-rename'),
  postcss = require('gulp-postcss'),
  autoprefixer = require('autoprefixer'),
  postcssInlineSvg = require('postcss-inline-svg'),
  browserSync = require('browser-sync').create()
pxtorem = require('postcss-pxtorem'),
  postcssProcessors = [
    postcssInlineSvg({
      removeFill: true,
      paths: ['./node_modules/bootstrap-icons/icons']
    }),
    pxtorem({
      propList: ['font', 'font-size', 'line-height', 'letter-spacing', '*margin*', '*padding*'],
      mediaQuery: true
    })
  ];

const paths = {
  scss: {
    src: './scss/style.scss',
    dest: './css',
    watch: './scss/**/*.scss',
    bootstrap: './node_modules/@asu-design-system/bootstrap4-theme/src/scss/bootstrap-asu.scss',
  },
  js: {
    bootstrap: './node_modules/bootstrap/dist/js/bootstrap.min.js',
    jquery: './node_modules/jquery/dist/jquery.min.js',
    popper: './node_modules/popper.js/dist/umd/popper.min.js',
    poppermap: './node_modules/popper.js/dist/umd/popper.min.js.map',
    dest: './js'
  },
  img: {
    png: './node_modules/@asu-design-system/bootstrap4-theme/dist/img/**/*.png',
    svg: './node_modules/@asu-design-system/bootstrap4-theme/dist/img/**/*.svg',
    ico: './node_modules/@asu-design-system/bootstrap4-theme/dist/img/**/*.ico',
    dest: './images'
  }
}

// Compile sass into CSS & auto-inject into browsers
function styles() {
  return gulp.src([paths.scss.bootstrap, paths.scss.src])
    .pipe(sourcemaps.init())
    .pipe(sass({
      includePaths: [
        // './node_modules/bootstrap/scss',
        '../../contrib/bootstrap_barrio/scss'
      ]
    }).on('error', sass.logError))
    .pipe($.postcss(postcssProcessors))
    .pipe(postcss([autoprefixer({
      browsers: [
        'Chrome >= 35',
        'Firefox >= 38',
        'Edge >= 12',
        'Explorer >= 10',
        'iOS >= 8',
        'Safari >= 8',
        'Android 2.3',
        'Android >= 4',
        'Opera >= 12']
    })]))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(paths.scss.dest))
    .pipe(cleanCss())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(paths.scss.dest))
    .pipe(browserSync.stream())
}

// Move the javascript files into our js folder
// removed  paths.js.asuheader from below
function js() {
  return gulp.src([paths.js.bootstrap, paths.js.jquery, paths.js.popper, paths.js.poppermap])
    .pipe(gulp.dest(paths.js.dest))
    .pipe(browserSync.stream())
}

// Move ASU Design System images into our theme
function images() {
  return gulp.src([paths.img.png, paths.img.svg, paths.img.ico])
    .pipe(gulp.dest(paths.img.dest))
}

// Static Server + watching scss/html files
function serve() {
  browserSync.init({
    proxy: 'http://localhost:8000/',
  })

  gulp.watch([paths.scss.watch, paths.scss.bootstrap], styles).on('change', browserSync.reload)
}

const build = gulp.series(styles, images, gulp.parallel(js, serve))

exports.styles = styles
exports.js = js
exports.serve = serve

exports.default = build
