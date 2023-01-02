const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,

	entry: {
		'recipe-roundups-block': path.resolve( process.cwd(), 'src/block.js' ),
	},

	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'dist/' ),
	},
};
