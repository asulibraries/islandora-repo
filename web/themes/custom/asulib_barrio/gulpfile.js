let gulp = require('gulp'),
  sass = require('gulp-sass')(require('sass')),
  sourcemaps = require('gulp-sourcemaps'),
  $ = require('gulp-load-plugins')(),
  cleanCss = require('gulp-clean-css'),
  rename = require('gulp-rename'),
  postcss = require('gulp-postcss'),
  autoprefixer = require('autoprefixer'),
  postcssInlineSvg = require('postcss-inline-svg'),
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
    bootstrap: './node_modules/@asu/unity-bootstrap-theme/src/scss/unity-bootstrap-theme.bundle.scss',
  },
  js: {
    bootstrap: './node_modules/bootstrap/dist/js/bootstrap.min.js',
    popper: './node_modules/@popperjs/core/dist/umd/popper.min.js',
    barrio: '../../contrib/bootstrap_barrio/js/barrio.js',
    poppermap: './node_modules/popper.js/dist/umd/popper.min.js.map',
    dest: './js'
  },
  img: {
    png: './node_modules/@asu/unity-bootstrap-theme/dist/img/**/*.png',
    svg: './node_modules/@asu/unity-bootstrap-theme/dist/img/**/*.svg',
    ico: './node_modules/@asu/unity-bootstrap-theme/dist/img/**/*.ico',
    dest: './images'
  }
}
//     // barrio: '../../contrib/bootstrap_barrio/js/barrio.js',

// Compile sass into CSS & auto-inject into browsers
function styles() {
  return gulp.src([paths.scss.bootstrap, paths.scss.src])
    .pipe(sourcemaps.init())
    .pipe(sass({
      includePaths: [
        '../../contrib/bootstrap_barrio/scss',
        'node_modules'
      ]
    }).on('error', sass.logError))
    .pipe($.postcss(postcssProcessors))
    .pipe(postcss([autoprefixer()]))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(paths.scss.dest))
    .pipe(cleanCss())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(paths.scss.dest))
}

// Move the javascript files into our js folder
// removed  paths.js.asuheader from below
function js() {
  return gulp.src([paths.js.bootstrap, paths.js.popper, paths.js.barrio])
    .pipe(gulp.dest(paths.js.dest))
}

// Move ASU Design System images into our theme
function images() {
  return gulp.src([paths.img.png, paths.img.svg, paths.img.ico])
    .pipe(gulp.dest(paths.img.dest))
}

const build = gulp.series(styles, images, js)

exports.styles = styles
exports.js = js

exports.default = build
