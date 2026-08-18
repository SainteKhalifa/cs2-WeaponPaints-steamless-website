(function () {
	'use strict';

	// Served by PHP so the address lives in one place: class/inspect.php.
	var VIEWER_URL = (window.cs2AppConfig || {}).inspectViewerUrl || '';

	document.addEventListener('DOMContentLoaded', function () {
		var modalEl = document.getElementById('inspectModal');
		if (modalEl && window.bootstrap) {
			var modal = new bootstrap.Modal(modalEl);
			var openLink = modalEl.querySelector('[data-inspect-open-link]');
			var label = modalEl.querySelector('[data-inspect-label]');
			var input = modalEl.querySelector('[data-inspect-input]');
			var defindexField = modalEl.querySelector('[data-inspect-defindex-field]');
			var pasteButton = modalEl.querySelector('[data-inspect-paste]');

			document.querySelectorAll('[data-inspect-open]').forEach(function (button) {
				button.addEventListener('click', function () {
					var hex = button.getAttribute('data-inspect-hex') || '';
					if (!hex) return;

					if (defindexField) defindexField.value = button.getAttribute('data-inspect-defindex') || '';
					if (label) label.textContent = button.getAttribute('data-inspect-label') || '';
					if (input) input.value = '';
					if (openLink) {
						// No viewer address means the config never reached the page;
						// a bare hex href would just reload the site.
						openLink.href = VIEWER_URL ? VIEWER_URL + hex : '#';
						openLink.classList.toggle('disabled', !VIEWER_URL);
					}
					modal.show();
				});
			});

			if (pasteButton && navigator.clipboard && navigator.clipboard.readText) {
				pasteButton.hidden = false;
				pasteButton.addEventListener('click', function () {
					navigator.clipboard.readText().then(function (text) {
						if (!input || !text) return;
						input.value = text.trim();
						input.focus();
					}).catch(function () {
						if (input) input.focus();
					});
				});
			}

			modalEl.addEventListener('shown.bs.modal', function () {
				if (input) input.focus();
			});
		}

		// The plugin keeps incrementing the StatTrak counters in game, and
		// nothing else would ever refresh them short of reloading the page.
		var badges = document.querySelectorAll('[data-stattrak-badge]');
		var countsUrl = (window.cs2AppConfig || {}).stattrakCountsUrl || '';
		if (!badges.length || !countsUrl) return;

		var refresh = function () {
			// A hidden tab would keep polling for nothing.
			if (document.hidden) return;

			fetch(countsUrl, { headers: { 'X-Requested-With': 'fetch' } })
				.then(function (response) { return response.ok ? response.json() : null; })
				.then(function (payload) {
					if (!payload || !payload.ok || !payload.counts) return;

					badges.forEach(function (badge) {
						var value = payload.counts[badge.getAttribute('data-stattrak-badge')];
						if (value === undefined) return;

						var slot = badge.querySelector('[data-stattrak-badge-count]');
						if (slot && slot.textContent !== String(value)) {
							slot.textContent = value;
						}

						// Keep the dialog in step, unless the player is editing
						// that very field right now.
						var card = badge.closest('.skin-card');
						var field = card ? card.querySelector('[data-stattrak-input]') : null;
						if (field && field !== document.activeElement) {
							field.value = value;
						}
					});
				})
				.catch(function () { /* a lost poll is picked up by the next one */ });
		};

		setInterval(refresh, 30000);
		document.addEventListener('visibilitychange', function () {
			if (!document.hidden) refresh();
		});
	});
})();
