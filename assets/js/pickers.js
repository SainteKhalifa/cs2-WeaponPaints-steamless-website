(function (app) {
	'use strict';
	var appConfig = app.config || {};
	var appText = appConfig.text || {};
	var observePickerImages = app.observePickerImages;
			var filterPickerResults = function (modal) {
				if (!modal) return;
				var input = modal.querySelector('[data-picker-search]');
				var query = input ? input.value.trim().toLowerCase() : '';
				var terms = query ? query.split(/\s+/).filter(Boolean) : [];
				modal.querySelectorAll('[data-picker-result]').forEach(function (result) {
					var searchText = (result.dataset.search || result.textContent || '').toLowerCase();
					var id = result.value || result.dataset.id || '';
					result.hidden = !!query && String(id) !== query && !terms.every(function (term) {
						return searchText.indexOf(term) !== -1;
					});
				});
				observePickerImages(modal);
			};
			document.querySelectorAll('.skin-picker-modal, .agent-picker-modal').forEach(function (modal) {
				var search = modal.querySelector('[data-picker-search]');
				if (search) {
					search.addEventListener('input', function () {
						filterPickerResults(modal);
					});
				}
				modal.addEventListener('show.bs.modal', function () {
					if (search) search.value = '';
					filterPickerResults(modal);
				});
				modal.addEventListener('shown.bs.modal', function () {
					observePickerImages(modal);
					if (search) search.focus();
				});
			});


})(window.cs2App = window.cs2App || {});
