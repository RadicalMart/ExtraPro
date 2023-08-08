/*
 * @package     ExtraPro Plugin
 * @subpackage  plg_system_extrapro
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2023 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

"use strict";

class ExtraProAjax {
	constructor() {
		this.options = null;
		this.controller = null;
	}

	setVariable(key, value) {
		this[key] = value;
	}

	getVariable(key, defaultValue = null) {
		return (!this[key] || this[key] === null) ? defaultValue : this[key];
	}

	sendAjax(action = null, data = {}) {
		return new Promise((success, error) => {
			if (action === null) {
				return error({message: 'Action is empty'});
			}

			let controller = this.controller;
			if (!controller) {
				return error({message: 'Controller not found'});
			}

			let isFormData = (data instanceof FormData),
				formData = (isFormData) ? data : new FormData();
			if (!isFormData) {
				formData = this.objectToFormData(data, formData);
			}
			formData.set('action', action);

			this.sendRequest(controller, formData)
				.then((s) => success(s))
				.catch((e) => error(e))
		});
	}

	objectToFormData(data = {}, formData = null, path = null) {
		if (formData === null) {
			formData = new FormData();
		}

		if (path === null) {
			path = '';
		}

		Object.keys(data).forEach((key) => {
			let name = (path) ? path + '[' + key + ']' : key,
				value = data[key];
			if (Array.isArray(value)) {
				value.forEach((val) => {
					formData.append(name + '[]', val)
				})
			} else if (typeof value === 'object') {
				formData = this.objectToFormData(value, formData, name);
			} else {
				formData.set(name, value);
			}
		});

		return formData;
	}

	sendRequest(controller, formData) {
		return new Promise((success, error) => {
			Joomla.request({
				url: controller,
				data: formData,
				method: 'POST',
				onSuccess: (response) => {
					try {
						response = JSON.parse(response);
						if (response.success) {
							return success(response.data);
						} else {
							return error({message: response.message});
						}
					} catch (je) {
						return error(je);
					}
				},
				onError: (e) => {
					let errorObject = this.parseJoomlaRequestError(e);
					if (errorObject) {
						return error(errorObject);
					}
				}
			});
		});
	}

	parseJoomlaRequestError(error) {
		if (error instanceof XMLHttpRequest) {
			if (error.status === 0) {

				console.error('aborted');
				return false;
			}

			let message;
			if (error.response) {
				let responseElement = document.createElement('div');
				responseElement.innerHTML = error.response;
				if (responseElement) {
					responseElement = responseElement.querySelector('title');
					if (responseElement) {
						message = responseElement.textContent;
					}
				}
			} else {
				message = error.status + ' ' + error.statusText;
			}

			return {'message': message};
		}

		if (typeof error === 'object') {
			if (error.message === 'Request aborted'
				|| error.message === null
				|| error.message === ''
				|| error.message === 0
				|| error.message === '0') {

				console.error('aborted');
				return false;
			}
		} else {
			return error;
		}
	}

	appendHtml(parent, html) {
		if (!parent) {
			return;
		}

		// Append new element
		let newElement = document.createElement('div');
		newElement.innerHTML = html;

		console.log(html);

		console.log(newElement.firstChild);
		parent.appendChild(newElement.firstChild);
	}
}

export default ExtraProAjax;