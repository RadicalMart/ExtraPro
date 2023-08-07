/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2023 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

const entry = {
	"administrator/config": {
		import: './plg_system_extrapro/es6/administrator/config.es6',
		filename: 'administrator/config.js',
	},
};

const webpackConfig = require('./webpack.config.js');
const publicPath = '../media';
const development = webpackConfig(entry, publicPath, 'development');

module.exports = [development]