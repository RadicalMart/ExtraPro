/*
 * @package     ExtraPro Plugin
 * @subpackage  plg_system_extrapro
 * @version     1.0.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2024 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

"use strict";

import ExtraProAjax from "../util/ajax.es6";

class ExtraProOverrides extends ExtraProAjax {
	constructor() {
		super();
		this.options = Joomla.getOptions('extrapro_overrides');
		this.controller = (this.options && this.options.controller) ? this.options.controller : false
	}

	insertHTML() {
		let tab = document.querySelector('#content joomla-tab#myTab joomla-tab-element#overrides');
		if (!tab || tab.getAttribute('extrapro-overrides-load') === 'true') {
			return;
		}
		let row = tab.querySelector('.row');
		if (!row) {
			return;
		}
		this.sendAjax('getOverridesHtml', {'extension_id': this.options.extension_id}).then((response) => {
			if (!response) {
				return;
			}
			row.innerHTML += response;

			document.querySelectorAll(
				'.folder-url, .component-folder-url, .plugin-folder-url, .layout-folder-url')
				.forEach((element) => {
					let clone = element.cloneNode(true);
					element.parentNode.replaceChild(clone, element);
				})

			tab.setAttribute('extrapro-overrides-load', 'true');

			Joomla.optionsStorage['joomla.messages'] = [];
			document.dispatchEvent(new Event('DOMContentLoaded', {'bubbles': true}));
		}).catch((error) => {
			Joomla.renderMessages({
				error: [error.message]
			});
			console.error(error.message);
		})
	}
}

export default ExtraProOverrides;

window.ExtraProOverridesClass = null;
window.ExtraProOverrides = () => {
	if (window.ExtraProOverridesClass === null) window.ExtraProOverridesClass = new ExtraProOverrides();
	return window.ExtraProOverridesClass;
}

document.addEventListener('DOMContentLoaded', () => {
	window.ExtraProOverrides().insertHTML();
});