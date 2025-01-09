/**
 * Adds Webpack entry points for customizations to core blocks
 * in addition to wp-scripts default configs that are used for custom blocks
 */

/**
 * External dependencies
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

/**
 * Add files are exported to in the /build folder
 */
module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		'checkout-button-block/index': path.resolve(
			process.cwd(),
			'src/checkout-button-block',
			'index.js'
		),
	},
};
