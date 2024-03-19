/*
 * @package     ExtraPro Plugin
 * @subpackage  plg_system_extrapro
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2024 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

"use strict";

/*
 * @package     ExtraPro Plugin
 * @subpackage  plg_system_extrapro
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2024 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

"use strict";

import ExtraProAjax from "../util/ajax.es6";

class ExtraProToolbar extends ExtraProAjax {
	constructor() {
		super();
		this.options = Joomla.getOptions('extrapro_toolbar');
		this.controller = (this.options && this.options.controller) ? this.options.controller : false
	}

	insertHTML() {
		this.sendAjax('getToolbarHtml', {
			'style_id': this.options.style_id,
			'context': this.options.context,
			'return': window.location.href,
		}).then((response) => {
			if (!response) {
				return;
			}
			document.body.insertAdjacentHTML('beforeend', response);
		}).catch((error) => {
			console.error(error.message);
		})
	}
}

export default ExtraProToolbar;

window.ExtraProToolbarClass = null;
window.ExtraProToolbar = () => {
	if (window.ExtraProToolbarClass === null) window.ExtraProToolbarClass = new ExtraProToolbar();
	return window.ExtraProToolbarClass;
}

document.addEventListener('DOMContentLoaded', () => {
	window.ExtraProToolbar().insertHTML();
});