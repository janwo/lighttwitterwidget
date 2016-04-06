var gulp = require('gulp');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var nano = require('gulp-cssnano');

var run_js = function () {
	return gulp.src('src/script.js')
		.pipe(uglify({
			preserveComments: 'some'
		}))
		.pipe(rename({
			suffix: '.min'
		} ))
		.pipe(gulp.dest( 'dst' ));
};
var run_css = function () {
	return gulp.src('src/style.scss')
		.pipe(sass())
		.pipe(nano({ autoprefixer: { browsers: [ '> 5%', 'last 2 versions' ], add: true } }))
		.pipe(rename({
			suffix: '.min',
			extension: '.css'
		}))
		.pipe(gulp.dest( 'dst' ));
};

gulp.task('default', function () {
	run_js();
	run_css();
	gulp.watch('src/*.js', run_js);
	gulp.watch('src/*.scss', run_css);
});
