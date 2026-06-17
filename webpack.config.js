/**
 * Extends the @wordpress/scripts default webpack config to build both blocks
 * from blocks/<name>/ into build/<name>/, copying each block.json next to its
 * compiled bundle (and its generated *.asset.php).
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

module.exports = {
	...defaultConfig,
	entry: {
		'calendar/index': path.resolve( process.cwd(), 'blocks', 'calendar', 'index.js' ),
		'event/index': path.resolve( process.cwd(), 'blocks', 'event', 'index.js' ),
		'featured/index': path.resolve( process.cwd(), 'blocks', 'featured', 'index.js' ),
		'countdown/index': path.resolve( process.cwd(), 'blocks', 'countdown', 'index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'build' ),
	},
	plugins: [
		...defaultConfig.plugins,
		new CopyWebpackPlugin( {
			patterns: [
				{ from: 'blocks/calendar/block.json', to: 'calendar/block.json' },
				{ from: 'blocks/event/block.json', to: 'event/block.json' },
				{ from: 'blocks/featured/block.json', to: 'featured/block.json' },
				{ from: 'blocks/countdown/block.json', to: 'countdown/block.json' },
			],
		} ),
	],
};
