(function () {
	'use strict';

	// Knives and gloves need a model before a finish, which used to mean two
	// buttons on a card that already struggled to fit four. The category button
	// is gone: "Skin" opens the model picker, and once a model is chosen the
	// finish picker opens on its own.
	//
	// Choosing a model submits the form and reloads the page, so the intent is
	// parked in sessionStorage: it survives the round trip, belongs to this tab
	// alone, and is dropped as soon as it has been used.
	var STORAGE_KEY = 'cs2-open-picker';

	var remember = function (modalId) {
		try {
			sessionStorage.setItem(STORAGE_KEY, modalId);
		} catch (error) {
			// Private browsing can refuse storage; the player just clicks Skin again.
		}
	};

	var takePending = function () {
		try {
			var pending = sessionStorage.getItem(STORAGE_KEY);
			sessionStorage.removeItem(STORAGE_KEY);
			return pending;
		} catch (error) {
			return null;
		}
	};

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-chain-picker]').forEach(function (button) {
			button.addEventListener('click', function () {
				remember(button.getAttribute('data-chain-picker') || '');
			});
		});

		var pending = takePending();
		if (!pending || !window.bootstrap) {
			return;
		}

		var target = document.getElementById(pending);
		if (!target) {
			return;
		}

		// A model with no finish of its own leaves the picker empty and disabled;
		// opening it would only be a dead end.
		if (target.querySelector('.skin-result')) {
			bootstrap.Modal.getOrCreateInstance(target).show();
		}
	});
})();
