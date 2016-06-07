/**
 * Mageplace Backup
 *
 * @category    design
 * @package     default_default
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

var MP = {};
MP.Backup = Class.create();
MP.Backup.prototype = {
	loadingAreaId: 'mpbackup-loading',
	loadingMaskId: 'loading-mask',
	loadingMaskLoaderId: 'loading_mask_loader',
	loadingMaskLoaderClass: 'backup-loader',
	globalCancelFunctionName: 'mpbackupCancel',
	cancelButtonClassName: 'cancel-button',
	loadingDisableClassName: 'mpbackuploaddisable',

	processRequestPeriod: 1, /* sec */
	isMultiStep: 1,
	isLogDisabled: 0,
	editFormId: '',
	editFormData: {},
	progressAreaName: '',
	backupFinished: 0,
	startBackupUrl: 'it will be changed to startBackupUrl',
	changeProfileUrl: 'it will be changed to changeProfileUrl',
	stepBackupUrl: 'it will be changed to stepBackupUrl',
	finishBackupUrl: 'it will be changed to finishBackupUrl',
	cancelBackupUrl: 'it will be changed to cancelBackupUrl',
	startProgressBackupUrl: 'it will be changed to startProgressBackupUrl',
	progressBackupUrl: 'it will be changed to progressBackupUrl',

	backupErrors: [],
	isLoaderLoaded: 0,
	startedRequests: 0,
	startedProgressRequests: 0,
	backupItem: {},
	logItemsLast: 0,
	logItems: [],
	logIntervalTimer: null,
	waitIntervalTimer: null,
	showLogInterval: 1, /* sec */
	isMainProcessFinished: 0,
	isStageProcessFinished: 0,
	isCancel: 0,
	isFirstStep: true,
	currentStep: 0,
	previousStep: '',
	progressPercents: 0,
	backupProcessFinished: false,


	initialize: function (obj) {
		for (var prop in obj) {
			this[prop] = obj[prop];
		}

		this.backupItem = new MP.Backup.Item();

		$(document.body).insert('<div id="' + this.loadingAreaId + '" style="display: none;"><div>&nbsp;</div></div>');
	},

	translate: function (text) {
		try {
			if (!Object.isUndefined(Translator)) {
				return Translator.translate(text);
			}
		} catch (e) {
		}

		return text;
	},

	changeProfile: function () {
		var profileId = new Number($('profile_id').getValue()).valueOf();
		if (profileId < 1) {
			return;
		}

		this.isLoaderLoaded = true;
		this.showLoader();

		location.href = this.changeProfileUrl + (this.changeProfileUrl.slice(-1) != '/' ? '/' : '') + 'profile_id/' + profileId;
	},

	loader: function (show) {
		if (show) {
			if (!this.isLoaderLoaded) {
				$(this.loadingMaskLoaderId).innerHTML = /*$(this.loadingMaskLoaderId).innerHTML +*/
					'<span class="warning">'
						+ this.translate('WARNING!') + '&nbsp;' + this.translate('Do not reload or close the page during backup process.')
						+ '</span>'
						+ (this.isMultiStep
						? '<span class="step-process-bar"><span id="progress"><!--x--></span><span id="step-count"></span></span><span id="step-name"></span>'
						: '')
						+ '<button class="' + this.cancelButtonClassName + '" onclick="javascript:' + this.globalCancelFunctionName + '()">' + this.translate('Cancel') + '</button>';
				$(this.loadingMaskLoaderId).addClassName(this.loadingMaskLoaderClass);
			}

			$$('.mpbackupprocessdisable').each(Element.hide);

			Element.show(this.loadingAreaId);
			Element.show(this.loadingMaskId);

			this.isLoaderLoaded = true;

		} else {
			$$('.mpbackupprocessdisable').each(Element.show);

			Element.hide(this.loadingMaskId);
			Element.hide(this.loadingAreaId);
		}
	},

	showLoader: function () {
		this.loader(true);
	},

	hideLoader: function () {
		this.loader(false);
	},

	setStepInfo: function (objStep) {
		if (!this.isMultiStep) {
			return;
		}

		if (Object.isUndefined(objStep)) {
			return;
		}

		if (!objStep.isNextStep()) {
			return;
		}

		this.currentStep++;

		var stepNum = this.backupItem.getStepNumber();
		if (!Object.isUndefined(stepNum) && stepNum) {
			$('step-count').innerHTML = this.translate("Step %1$d from %2$d").replace('%1$d', this.currentStep).replace('%2$d', stepNum);
		} else {
			$('step-count').innerHTML = this.translate("Step %d").replace('%d', this.currentStep);
		}

		var points = this.backupItem.getPointNumber();
		if (points) {
			var percentPoint;

			if (objStep.isFinishStep(this.previousStep)) {
				percentPoint = 100;
			} else {
				var stepPoint = objStep.getStepPoint(this.previousStep);
				percentPoint = this.progressPercents + Math.round(stepPoint / points * 100);
				if (percentPoint > 100) {
					percentPoint = 100;
				}
			}

			for (this.progressPercents; this.progressPercents < percentPoint && this.progressPercents < 100; this.progressPercents++) {
				setTimeout(function () {
					$('progress').setStyle({width: this.progressPercents + "%"})
				}.bind(this), 100);
			}
		}

		this.previousStep = objStep.getStep();

		var name = objStep.getStepName();
		if (name) {
			$('step-name').innerHTML = name;
		}
	},

	start: function () {
		this.startedRequests = 0;
		this.isFirstStep = true;
		this.isMainProcessFinished = 0;
		this.isStageProcessFinished = 0;

		if (!$('backup_name').value) {
			$('backup_name').value = this.translate('Backup - %s');
		}

		this.editFormData = $(this.editFormId).serialize();

		$('profile_id').setAttribute('disabled', 'true');
		$('backup_name').setAttribute('disabled', 'true');
		$('backup_filename').setAttribute('disabled', 'true');
		$('backup_description').setAttribute('disabled', 'true');

		this.showLoader();

		if ($(this.progressAreaName)) {
			$(this.progressAreaName).update('');
		}

		var requestCounterTrigger = false;

		new Ajax.Request(this.startBackupUrl, {
				loaderArea: false,
				parameters: this.editFormData,

				onCreate: function () {
					++this.startedRequests;
					this.setStepInfo(new MP.Backup.Step().setStartStep());
				}.bind(this),

				onComplete: function (transport) {
					if (this.checkFinish()) {
						if (!requestCounterTrigger) {
							--this.startedRequests;
							requestCounterTrigger = true;
						}

						return this.finish();
					}

					var backup = this.getResponse(transport);
					this.backupItem = new MP.Backup.Item(backup);

					if (this.backupItem.isError()) {
						throw new MP.Backup.Error(this.backupItem.getError());
					}

					if (!this.backupItem.getSecret()) {
						throw new MP.Backup.Error(this.translate('Backup code id error'));
					}

					if (!requestCounterTrigger) {
						--this.startedRequests;
						requestCounterTrigger = true;
					}

					this.step();

				}.bind(this),

				onException: function (obj, exception) {
					console.log(exception);
					if (!requestCounterTrigger) {
						--this.startedRequests;
						requestCounterTrigger = true;
					}
					this.error(exception, (this.backupItem.getSecret() ? true : false));
				}.bind(this)
			}
		);

		return true;
	},

	step: function (objStep) {
		if (this.checkFinish()) {
			return this.finish();
		}

		if (Object.isUndefined(objStep)) {
			objStep = new MP.Backup.Step().setSecret(this.backupItem.getSecret()).setFirstStep();
		}

		this.setStepInfo(objStep);

		var requestCounterTrigger = false;
		new Ajax.Request(this.stepBackupUrl, {
			loaderArea: false,
			parameters: objStep.toQueryString(),

			onCreate: function () {
				++this.startedRequests;

				if (this.isLogDisabled || !this.isFirstStep) {
					return;
				}

				this.startProgress();

			}.bind(this),

			onComplete: function (transport) {
				this.isFirstStep = false;

				if (this.checkFinish()) {
					if (!requestCounterTrigger) {
						--this.startedRequests;
						requestCounterTrigger = true;
					}

					return this.finish();
				}

				var step = new MP.Backup.Step(this.getResponse(transport));
				if (step.getError() != '') {
					throw new MP.Backup.Error(step.getError());
				}

				if (!requestCounterTrigger) {
					--this.startedRequests;
					requestCounterTrigger = true;
				}

				if (!step.isFinished()) {
					this.step(step);
				} else {
					this.isMainProcessFinished = 1;
					this.finish();
				}
			}.bind(this),

			onException: function (obj, exception) {
				console.log(exception);
				if (!requestCounterTrigger) {
					--this.startedRequests;
					requestCounterTrigger = true;
				}
				this.error(exception, true);
			}.bind(this)
		});
	},

	startProgress: function () {
		var requestCounterTrigger = false;
		new Ajax.Request(this.startProgressBackupUrl, {
			loaderArea: false,

			parameters: {
				secret: this.backupItem.getSecret()
			},

			onCreate: function () {
				++this.startedRequests;
			}.bind(this),

			onComplete: function (transport) {
				if (this.checkFinish()) {
					if (!requestCounterTrigger) {
						--this.startedRequests;
						requestCounterTrigger = true;
					}
					return this.finish();
				}

				var mpbackupProgress = new MP.Backup.Progress(this.getResponse(transport));

				if (mpbackupProgress.hasError()) {
					console.log(mpbackupProgress.getError());
				}

				if (!requestCounterTrigger) {
					--this.startedRequests;
					requestCounterTrigger = true;
				}

				if (mpbackupProgress.isFinished()) {
					this.isStageProcessFinished = 1;
				} else {
					this.progress();
				}

				this.showLogs();
				this.logIntervalTimer = setInterval(this.showLogs.bind(this), this.showLogInterval * 1000);

			}.bind(this),

			onException: function (obj, exception) {
				console.log(exception);
				if (!requestCounterTrigger) {
					--this.startedRequests;
					requestCounterTrigger = true;
				}
				this.error(exception, false);
			}.bind(this)
		});
	},

	progress: function (finish) {
		if (this.isStageProcessFinished || this.isCancel) {
			return;
		}

		if (this.startedProgressRequests != 0) {
			setTimeout(function () {
				this.progress(finish)
			}.bind(this), this.processRequestPeriod * 1000);

			return;
		}

		if (!Object.isUndefined(finish) && finish == true) {
			var async = false;
			var sendErrorRequest = false;
			finish = 1;
		} else {
			var async = true;
			var sendErrorRequest = true;
			finish = 0;
		}

		var requestCounterTrigger = false;
		new Ajax.Request(this.progressBackupUrl, {
			loaderArea: false,
			asynchronous: async,

			parameters: {
				finish: finish
			},

			onCreate: function () {
				++this.startedRequests;
				++this.startedProgressRequests;
			}.bind(this),

			onComplete: function (transport) {
				var response = this.getResponse(transport);
				var mpbackupProgress = new MP.Backup.Progress(response);

				if (mpbackupProgress.hasError()) {
					console.log(mpbackupProgress.getError());
				}

				if (finish || mpbackupProgress.isFinished()) {
					this.isStageProcessFinished = 1;
				}

				if (mpbackupProgress.items.length > 0) {
					this.logItems = this.logItems.concat(mpbackupProgress.items);
				}

				if (mpbackupProgress.getText() && mpbackupProgress.getText() != '...') {
					this.logItems.push(new MP.Backup.Progress.Item().setLog(mpbackupProgress.getText()));
				}

				if (!requestCounterTrigger) {
					--this.startedRequests;
					--this.startedProgressRequests;
					requestCounterTrigger = true;
				}

				if (!this.isStageProcessFinished) {
					setTimeout(function () {
						this.progress()
					}.bind(this), this.processRequestPeriod * 1000);
				}

			}.bind(this),

			onException: function (obj, exception) {
				console.log(exception);
				if (!requestCounterTrigger) {
					--this.startedRequests;
					--this.startedProgressRequests;
					requestCounterTrigger = true;
				}
				this.error(exception, sendErrorRequest);
			}.bind(this)
		});
	},

	showLogs: function (finish) {
		if (Object.isUndefined(finish)) {
			finish = false;
		}

		if (finish == true) {
			this.waitLog(true);
			if (this.logIntervalTimer) {
				clearInterval(this.logIntervalTimer);
			}
		}

		try {
			if (this.logItems.length > this.logItemsLast) {
				this.waitLog(true);

				for (var i = this.logItemsLast; i < this.logItems.length; i++) {
					/** @var MP.Backup.Progress.Item this.logItems[i] */
					this.logItems[i].insert(this.progressAreaName);
				}

				this.logItemsLast = i;
			} else if (!finish) {
				this.waitLog();
			}
		} catch (e) {
			console.log(e);
		}
	},

	waitLog: function (stop) {
		if (Object.isUndefined(stop)) {
			stop = false;
		}

		if (stop) {
			if ($('wait-log-row')) {
				$('wait-log-row').remove();
			}
		} else {
			if (!$('wait-log-row')) {
				$(this.progressAreaName).insert({top: '<div id="wait-log-row"><!-- x --></div>'});
				/*$('wait-log-row').addClassName('circle');*/
				/*$('wait-log-row').addClassName('circle-small');*/
			}
		}
	},

	checkFinish: function () {
		if ((this.isMainProcessFinished && this.isStageProcessFinished) || this.isCancel) {
			return true;
		}

		return false;
	},

	finish: function () {
		if (this.startedProgressRequests != 0) {
			setTimeout(this.finish.bind(this), 1000);
		}

		var finish = true;
		this.progress(finish);
		this.showLogs(finish);

		this.isMainProcessFinished = 1;
		this.isStageProcessFinished = 1;

		if (this.backupErrors.length) {
			var errorRow = '<tr><td class="label">'
				+ '<label for="errors_area">' + this.translate('Backup Errors') + '</label>'
				+ '</td><td class="value"><span id="errors_area">';
			for (var i = 0; i < this.backupErrors.length; i++) {
				errorRow += new MP.Backup.Row(this.backupErrors[i]).setErrorType().toString();
			}
			errorRow += '</span></td></tr>';

			$(this.progressAreaName).up().up().up().insert({before: errorRow});

			this.backupErrors = [];
		}

		if (this.isCancel) {
			this.hideLoader();
			$$('.' + this.loadingDisableClassName).each(Element.remove);
		} else {
			location.href = location.href.replace('create', 'finishBackup/backup_code/' + this.backupItem.getSecret());
		}

		this.backupProcessFinished = true;

		return true;
	},

	cancel: function (cancelRequest) {
		if (Object.isUndefined(cancelRequest)) {
			cancelRequest = true;
		}

		this.isCancel = 1;

		$$('.' + this.cancelButtonClassName).each(function (el) {
			el.disabled = true;
			el.addClassName('back')
		});

		$$('.' + this.loadingDisableClassName).each(Element.remove);

		if (cancelRequest) {
			var requestCounterTrigger = false;
			new Ajax.Request(this.cancelBackupUrl, {
				loaderArea: false,
				asynchronous: false,

				parameters: {
					secret: this.backupItem.getSecret()
				},

				onCreate: function () {
					++this.startedRequests;
				}.bind(this),

				onComplete: function () {
					if (!requestCounterTrigger) {
						--this.startedRequests;
						requestCounterTrigger = true;
					}
				}.bind(this),

				onException: function (obj, exception) {
					console.log(exception);
					if (!requestCounterTrigger) {
						--this.startedRequests;
						requestCounterTrigger = true;
					}
					this.insertRow(exception, MP.Backup.LogLevels.getError());
				}.bind(this)
			});

			requestCounterTrigger = false;
			new Ajax.Request(this.finishBackupUrl, {
				loaderArea: false,
				asynchronous: false,

				parameters: {
					secret: this.backupItem.getSecret(),
					cancel: 1
				},

				onCreate: function () {
					++this.startedRequests;
				}.bind(this),

				onComplete: function (transport) {
					if (this.checkFinish()) {
						if (!requestCounterTrigger) {
							--this.startedRequests;
							requestCounterTrigger = true;
						}

						return this.finish();
					}

					var errorResponse = this.getResponse(transport);
					var finish = new MP.Backup.Finish(errorResponse);
					if (!finish.isFinish() && finish.isError()) {
						throw new MP.Backup.Error(finish.getError());
					}

					if (!requestCounterTrigger) {
						--this.startedRequests;
						requestCounterTrigger = true;
					}
				}.bind(this),

				onException: function (obj, exception) {
					console.log(exception);
					if (!requestCounterTrigger) {
						--this.startedRequests;
						requestCounterTrigger = true;
					}
					this.error(exception, 'critical');
				}.bind(this)
			});
		}

		try {
			this.finish();
		} catch (e) {
			console.log(e);
		}
	},

	error: function (error, sendRequest) {
		if (!Object.isString(error)) {
			error = error.toString();
		}

		error = error.replace(/\n/gm, '');

		var exists = false;
		for (var i = 0; i < this.backupErrors.length; i++) {
			if (error == this.backupErrors[i]) {
				exists = true;
				break;
			}
		}

		if (!exists) {
			this.backupErrors[this.backupErrors.length] = error;
		}

		if (!Object.isUndefined(sendRequest) && sendRequest) {
			if (sendRequest === 'critical') {
				this.finishCriticalBackup(error);
			} else {
				this.finishErrorBackup(error);
			}
		} else {
			this.cancel();
		}
	},

	finishErrorBackup: function (error) {
		if (Object.isUndefined(error) || !error) {
			this.error('Error reason undefined', 'critical');
			return;
		}

		var requestCounterTrigger = false;
		new Ajax.Request(this.finishBackupUrl, {
			loaderArea: false,
			asynchronous: false,

			parameters: {
				secret: this.backupItem.getSecret(),
				error: error
			},

			onCreate: function () {
				++this.startedRequests;
			}.bind(this),

			onComplete: function (transport) {
				if (this.checkFinish()) {
					if (!requestCounterTrigger) {
						--this.startedRequests;
						requestCounterTrigger = true;
					}

					return this.finish();
				}

				var errorResponse = this.getResponse(transport);
				var finish = new MP.Backup.Finish(errorResponse);
				if (!finish.isFinish() && finish.isError()) {
					throw new MP.Backup.Error(finish.getError());
				}

				if (!requestCounterTrigger) {
					--this.startedRequests;
					requestCounterTrigger = true;
				}

				this.cancel(false);

			}.bind(this),

			onException: function (obj, exception) {
				console.log(exception);
				if (!requestCounterTrigger) {
					--this.startedRequests;
					requestCounterTrigger = true;
				}
				this.error(exception, 'critical');
			}.bind(this)
		});
	},

	finishCriticalBackup: function (error) {
		var requestCounterTrigger = false;
		new Ajax.Request(this.finishBackupUrl, {
			loaderArea: false,
			asynchronous: false,

			parameters: {
				secret: this.backupItem.getSecret(),
				critical: error
			},

			onCreate: function () {
				++this.startedRequests;
			}.bind(this),

			onComplete: function () {
				if (!requestCounterTrigger) {
					--this.startedRequests;
					requestCounterTrigger = true;
				}

				if (this.checkFinish()) {
					return this.finish();
				}

			}.bind(this),

			onException: function (obj, exception) {
				console.log(exception);
				if (!requestCounterTrigger) {
					--this.startedRequests;
					requestCounterTrigger = true;
				}
				this.insertRow(exception, MP.Backup.LogLevels.getError());
			}.bind(this)
		});

		this.cancel(false);
	},

	getResponse: function (transport) {
		if (transport.status != 200) {
			if (!transport.status) {
				if (this.backupProcessFinished) {
					return setTimeout(this.finish().bind(this), 60000);
				} else {
					throw new MP.Backup.Error(this.translate('Unknown response status'));
				}
			}

			var errorMessage = this.translate("Error %d: %s").replace('%d', transport.status).replace('%s', transport.statusText);
			if (transport.responseText) {
				if (transport.responseText.isJSON()) {
					console.log(transport.responseText);
				} else {
					errorMessage += (transport.statusText ? ' - ' : '') + transport.responseText;
				}
			}

			throw new MP.Backup.Error(errorMessage);
		}

		try {
			var stepResponse = transport.responseText.evalJSON();
		} catch (e) {
			console.log(e);
		}

		if (Object.isUndefined(stepResponse) || !stepResponse) {
			if (!Object.isUndefined(transport.responseText) && transport.responseText) {
				throw new MP.Backup.Error(transport.responseText);
			} else {
				throw new MP.Backup.Error(this.translate('Empty response body'));
			}
		}

		/* Check for Magento native errors */
		if (!Object.isUndefined(stepResponse.error) && !Object.isUndefined(stepResponse.message) && stepResponse.error && stepResponse.message) {
			this.error(stepResponse.message, 'critical');
		}

		return stepResponse;
	},

	insertRow: function (text, type) {
		if (!Object.isString(text)) {
			text = text.toString();
		}

		new MP.Backup.Row(text, type).insert(this.progressAreaName);
	}
};

MP.Backup.Error = Class.create();
MP.Backup.Error.prototype = {
	initialize: function (errorText) {
		this.message = errorText;
	},

	toString: function () {
		return this.message;
	}
};

MP.Backup.Row = Class.create();
MP.Backup.Row.prototype = {
	element: {},
	rowTag: 'p',
	rowText: '',
	rowType: '',

	initialize: function (rowText, rowType) {
		this.element = {}, this.rowText = '', this.rowType = '';

		if (Object.isString(rowText)) {
			this.rowText = rowText;
		}

		if (Object.isString(rowType)) {
			this.rowType = rowType;
		} else if (Object.isUndefined(rowType) || !rowType) {
			this.rowType = MP.Backup.LogLevels.getDebug();
		}

		this.element = new Element(this.rowTag);
	},

	setErrorType: function () {
		this.rowType = MP.Backup.LogLevels.getError();
		return this;
	},

	setWarningType: function () {
		this.rowType = MP.Backup.LogLevels.getWarning();
		return this;
	},

	setInfoType: function () {
		this.rowType = MP.Backup.LogLevels.getInfo();
		return this;
	},

	setDebugType: function () {
		this.rowType = MP.Backup.LogLevels.getDebug();
		return this;
	},

	insert: function (area) {
		$(area).insert({
			top: this.toString()
		});
	},

	toString: function () {
		var className;

		if (!this.rowType || MP.Backup.LogLevels.isDebug(this.rowType)) {
			className = '';
		} else if (MP.Backup.LogLevels.isError(this.rowType)) {
			className = 'log-error';
		} else if (MP.Backup.LogLevels.isWarning(this.rowType)) {
			className = 'log-warning';
		} else if (MP.Backup.LogLevels.isInfo(this.rowType)) {
			className = 'log-info';
		} else {
			className = '';
		}

		this.element.addClassName(className);

		this.element.update(this.rowText);

		return new MP.Backup.Element(this.element).toString();
	}
};

MP.Backup.Element = Class.create();
MP.Backup.Element.prototype = {
	initialize: function (elem) {
		this.element = elem;
	},

	toString: function () {
		if ("outerHTML" in this.element) {
			return this.element.outerHTML;
		} else {
			var wrapper = this.element.wrap('div');
			return wrapper.innerHTML;
		}
	}
};

MP.Backup.Methods = {
	toQueryString: function (obj, property, arrProperty) {
		return this.toQueryArray(obj, property, arrProperty).join('&');
	},

	toQueryArray: function (obj, propName, arrPropNames) {
		if (Object.isUndefined(obj)) {
			obj = this;
		}

		if (Object.isUndefined(arrPropNames)) {
			arrPropNames = [];
		}

		var arrQuery = [];
		for (property in obj) {
			if (Object.isFunction(obj[property])) {
				continue;
			}

			var arrLocalProp = arrPropNames.clone();

			if (typeof(obj[property]) == 'object' && obj[property]) {
				if (!Object.isUndefined(propName)) {
					arrLocalProp.push(propName);
				}

				if (Object.isArray(obj[property])) {
					if (obj[property].length) {
						arrQuery = arrQuery.concat(this.toQueryString(obj[property], property, arrLocalProp));
					}
				} else {
					arrQuery = arrQuery.concat(this.toQueryString(obj[property], property, arrLocalProp));
				}
			} else if (!Object.isUndefined(obj[property]) && obj[property]) {
				var pair = this.toQueryPair(property, obj[property], propName, arrPropNames);
				if (pair) {
					arrQuery.push(pair);
				}
			}
		}

		return arrQuery;
	},

	toQueryPair: function (key, value, propName, arrPropNames) {
		key = encodeURIComponent(key);
		if (!Object.isUndefined(propName)) {
			var propNames = '';
			if (Object.isArray(arrPropNames) && arrPropNames.length) {
				for (var i = 0; i < arrPropNames.length; i++) {
					if (i == 0) {
						propNames += encodeURIComponent(arrPropNames[i]);
					} else {
						propNames += '[' + encodeURIComponent(arrPropNames[i]) + ']';
					}
				}
			}

			propName = encodeURIComponent(propName);
			key = (propNames != '' ? propNames + '[' + propName + ']' : propName) + '[' + key + ']';
		}

		if (Object.isUndefined(value)) {
			return key + '=';
		} else {
			return key + '=' + encodeURIComponent(String.interpret(value));
		}
	}
};

var mpbackupInitClasses = function () {
	MP.Backup.Step.addMethods(MP.Backup.Methods);
};


var mpbackup, mpbackupCancel;
var mpbackupInit = function () {
	try {
		mpbackupInitClasses();

		if (Object.isUndefined(MP.Backup.InitData)
			|| Object.isUndefined(MP.Backup.Step)
			|| Object.isUndefined(MP.Backup.Item)
			|| Object.isUndefined(MP.Backup.LogLevels)
			|| Object.isUndefined(MP.Backup.Finish)
			|| Object.isUndefined(MP.Backup.Progress)
			|| Object.isUndefined(MP.Backup.Progress.Item)
			) {
			alert('Unrecoverable JS error. Empty initialize data. Contact MagePlace support.');
			return false;
		}

		mpbackup = new MP.Backup(MP.Backup.InitData);
		$$('.' + mpbackup.loadingDisableClassName).each(Element.show);

		mpbackupCancel = function () {
			mpbackup.cancel();
		};

	} catch (e) {
		alert(e);
	}
};

Event.observe(window, 'load', mpbackupInit);