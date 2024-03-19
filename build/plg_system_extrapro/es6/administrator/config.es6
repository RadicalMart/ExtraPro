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

document.addEventListener('DOMContentLoaded', () => {
	let tab = document.querySelector('#content joomla-tab#myTab joomla-tab-element#attrib-params');

	if (!tab || tab.getAttribute('extrapro-config-load') === 'true') {
		return;
	}

	// Prepare fancy select fix
	tab.querySelectorAll('joomla-field-fancy-select').forEach((oldElement) => {
		let field = oldElement.querySelector('select'),
			value = (field.multiple) ? JSON.stringify(oldElement.choicesInstance.getValue(true)) : field.value;
		oldElement.setAttribute('fancy-fix-value', value);
		oldElement.choicesInstance.destroy();
		field.value = value;

		let newElement = document.createElement('joomla-field-fancy-select-fix');
		for (let index = oldElement.attributes.length - 1; index > -1; --index) {
			let attribute = oldElement.attributes[index];
			newElement.setAttribute(attribute.name, attribute.value);
		}
		while (oldElement.firstChild) {
			newElement.appendChild(oldElement.firstChild);
		}
		oldElement.parentNode.replaceChild(newElement, oldElement);
	});

	tab.querySelectorAll('joomla-field-fancy-select').forEach((fancy) => {
		let field = fancy.querySelector('select'),
			value = field.value;
		field.setAttribute('data-value', value);
		fancy.choicesInstance.destroy();
	});

	// Create row
	let innerHTML = '<div class="row">';
	tab.querySelectorAll(':scope > fieldset.options-form').forEach((fieldset) => {
		innerHTML += '<div class="col-md-6 col-lg-4">' + fieldset.outerHTML + '</div>'
	})

	innerHTML += '</div>';
	tab.innerHTML = innerHTML;

	// ShowOn fix
	if (Joomla.Showon) {
		tab.querySelectorAll('[data-showon]').forEach((field) => {
			field.removeAttribute('data-showon-initialised');
		})
		Joomla.Showon.initialise(tab);
	}

	// Run DOMContentLoaded
	tab.setAttribute('extrapro-config-load', 'true');
	Joomla.optionsStorage['joomla.messages'] = [];
	document.dispatchEvent(new Event('DOMContentLoaded', {'bubbles': true}));

	// Fancy select fix
	tab.querySelectorAll('joomla-field-fancy-select-fix').forEach((oldElement) => {
		let newElement = document.createElement('joomla-field-fancy-select');
		for (let index = oldElement.attributes.length - 1; index > -1; --index) {

			let attribute = oldElement.attributes[index];
			newElement.setAttribute(attribute.name, attribute.value);
		}
		while (oldElement.firstChild) {
			newElement.appendChild(oldElement.firstChild);
		}
		oldElement.parentNode.replaceChild(newElement, oldElement);
	});

	tab.querySelectorAll('joomla-field-fancy-select[fancy-fix-value]').forEach((fancy) => {
		let field = fancy.querySelector('select'),
			value = (field.multiple) ? JSON.parse(fancy.getAttribute('fancy-fix-value'))
				: fancy.getAttribute('fancy-fix-value');
		fancy.choicesInstance.setChoiceByValue(value);
		fancy.removeAttribute('fancy-fix-value');
	});
});
