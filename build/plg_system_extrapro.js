const entry = {
	"administrator/config": {
		import: './plg_system_extrapro/es6/administrator/config.es6',
		filename: 'administrator/config.js',
	},
	"site/toolbar": {
		import: './plg_system_extrapro/es6/site/toolbar.es6',
		filename: 'site/toolbar.js',
	},
};

const webpackConfig = require('./webpack.config.js');
const publicPath = '../media';
const production = webpackConfig(entry, publicPath);
const development = webpackConfig(entry, publicPath, 'development');

module.exports = [production, development]