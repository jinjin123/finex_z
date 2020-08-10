var gulp = require('gulp');
var uglify = require('gulp-uglify');
var htmlmin = require('gulp-htmlmin');
var minifyCSS = require('gulp-minify-css');

//压缩js
gulp.task('js', function () {
    gulp.src([
        'assets/charting_library/**/*'
    ], {base: '.'}).pipe(gulp.dest('build'));
    gulp.src([
        'assets/js/libs/jquery.min.js',
        'assets/js/libs/moment.min.js',
        'assets/js/libs/pako.min.js',
        'assets/js/libs/require.min.js',
        'assets/js/libs/promise-polyfill.min.js',
        'assets/js/libs/es6-polyfill.min.js'
    ], {base: '.'}).pipe(gulp.dest('build'));
    return gulp.src([
        'assets/js/*.js',
        'assets/js/libs/utils.js',
        'assets/js/libs/config.js'
    ], {base: '.'}).pipe(uglify()).pipe(gulp.dest('build'));
});

//压缩css
gulp.task('style', function () {
    gulp.src('assets/fonts/*', {base: '.'}).pipe(gulp.dest('build'));
    return gulp.src('assets/css/*.css', {base: '.'}).pipe(minifyCSS()).pipe(gulp.dest('build'));
});

//压缩html
gulp.task('html', function () {
    return gulp.src('*.html').pipe(htmlmin({collapseWhitespace: true})).pipe(gulp.dest('build'));
});

gulp.task('default', gulp.series('js', 'style', 'html'));


