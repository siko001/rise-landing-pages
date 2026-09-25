module.exports = {
	presets: [ '@wordpress/babel-preset-default' ],
	plugins: [
		[
			'@babel/plugin-transform-react-jsx',
			{
				runtime: 'classic',
				useBuiltIns: true,
				pragma: 'wp.element.createElement',
				pragmaFrag: 'wp.element.Fragment',
			},
		],
	],
};
