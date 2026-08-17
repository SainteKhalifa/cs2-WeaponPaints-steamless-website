(function (app) {
	'use strict';
	var appConfig = app.config || {};
	var appText = appConfig.text || {};
			document.querySelectorAll('[data-skin-param-row]').forEach(function (row) {
				var range = row.querySelector('[data-skin-param-range]');
				var number = row.querySelector('[data-skin-param-number]');
				if (!range || !number) return;
				var spinnerStep = number.dataset.maxDecimals ? number.step : '';
				var enablePreciseInput = function () {
					if (spinnerStep) number.step = 'any';
				};
				if (spinnerStep) {
					enablePreciseInput();
					number.addEventListener('pointerdown', function () {
						number.step = spinnerStep;
					});
					number.addEventListener('pointerup', enablePreciseInput);
					number.addEventListener('pointercancel', enablePreciseInput);
					number.addEventListener('keydown', function (event) {
						if (event.key === 'ArrowUp' || event.key === 'ArrowDown') number.step = spinnerStep;
					});
					number.addEventListener('keyup', enablePreciseInput);
					number.addEventListener('blur', enablePreciseInput);
				}
				var clampValue = function (value, fallback) {
					var numeric = parseFloat(value);
					if (!isFinite(numeric)) return fallback;
					var min = parseFloat(number.min);
					var max = parseFloat(number.max);
					if (isFinite(min)) numeric = Math.max(min, numeric);
					if (isFinite(max)) numeric = Math.min(max, numeric);
					return numeric;
				};
				var formatNumberValue = function (value) {
					var maxDecimals = parseInt(number.dataset.maxDecimals || '', 10);
					if (!isFinite(maxDecimals)) return String(value);
					var formatted = Number(value).toFixed(maxDecimals).replace(/0+$/, '').replace(/\.$/, '');
					return formatted === '' || formatted === '-0' ? '0' : formatted;
				};
				range.addEventListener('input', function () {
					number.value = range.value;
				});
				number.addEventListener('input', function () {
					var numeric = parseFloat(number.value);
					if (isFinite(numeric)) range.value = String(clampValue(numeric, range.value));
				});
				number.addEventListener('change', function () {
					var value = clampValue(number.value, parseFloat(range.value) || 0);
					number.value = formatNumberValue(value);
					range.value = String(value);
				});
			});

			var validationMessages = {
				required: appText.validationRequired,
				numberRange: appText.validationNumberRange,
				decimalRange: appText.validationDecimalRange,
				integerRange: appText.validationIntegerRange
			};

			var fillValidationMessage = function (template, input) {
				return template
					.replace('{min}', input.min || '0')
					.replace('{max}', input.max || '')
					.replace('{decimals}', input.dataset.maxDecimals || '');
			};

			var decimalPlaces = function (value) {
				var normalized = String(value || '').trim();
				if (/[eE]/.test(normalized)) return Infinity;
				var match = normalized.match(/^-?\d*(?:\.(\d*))?$/);
				return match ? (match[1] || '').length : Infinity;
			};

			var validateLocalizedInput = function (input) {
				input.setCustomValidity('');
				if (input.validity.valueMissing) {
					input.setCustomValidity(validationMessages.required);
					return;
				}
				var maxDecimals = parseInt(input.dataset.maxDecimals || '', 10);
				if (isFinite(maxDecimals) && decimalPlaces(input.value) > maxDecimals) {
					input.setCustomValidity(fillValidationMessage(validationMessages.decimalRange, input));
					return;
				}
				if (input.type === 'number' && (input.validity.rangeUnderflow || input.validity.rangeOverflow || input.validity.stepMismatch)) {
					var step = parseFloat(input.step);
					var template = input.dataset.maxDecimals
						? validationMessages.decimalRange
						: (isFinite(step) && Number.isInteger(step) ? validationMessages.integerRange : validationMessages.numberRange);
					input.setCustomValidity(fillValidationMessage(template, input));
				}
			};

			document.querySelectorAll('input[required], input[type="number"][min], input[type="number"][max], [data-nametag-input], [data-stattrak-input]').forEach(function (input) {
				input.addEventListener('invalid', function () {
					validateLocalizedInput(input);
				});
				input.addEventListener('input', function () {
					validateLocalizedInput(input);
				});
				input.addEventListener('change', function () {
					validateLocalizedInput(input);
				});
				validateLocalizedInput(input);
			});
			document.querySelectorAll('[data-nametag-toggle]').forEach(function (toggle) {
				var row = toggle.closest('.nametag-row');
				var input = row ? row.querySelector('[data-nametag-input]') : null;
				var sync = function () {
					if (!input) return;
					input.classList.toggle('is-inactive', !toggle.checked);
					input.disabled = !toggle.checked;
					input.required = toggle.checked;
					validateLocalizedInput(input);
				};
				toggle.addEventListener('change', sync);
				sync();
			});
			document.querySelectorAll('[data-stattrak-toggle]').forEach(function (toggle) {
				var row = toggle.closest('.stattrak-row');
				var input = row ? row.querySelector('[data-stattrak-input]') : null;
				var sync = function () {
					if (!input) return;
					input.classList.toggle('is-inactive', !toggle.checked);
					input.disabled = !toggle.checked;
					input.required = toggle.checked;
					validateLocalizedInput(input);
				};
				toggle.addEventListener('change', sync);
				sync();
			});

})(window.cs2App = window.cs2App || {});
