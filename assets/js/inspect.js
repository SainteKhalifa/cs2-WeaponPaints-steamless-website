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

		// Confirmation bubble for anything carrying data-confirm.
		//
		// The trigger lives inside a modal that clips its overflow, so a single
		// bubble is kept on the body and positioned from the trigger's rectangle.
		(function () {
			var text = (window.cs2AppConfig || {}).text || {};
			var bubble = null;
			var trigger = null;

			var close = function () {
				if (!bubble) return;
				bubble.classList.remove('is-open');
				if (trigger) {
					trigger.setAttribute('aria-expanded', 'false');
					trigger.focus();
					trigger = null;
				}
			};

			var place = function (target) {
				var r = target.getBoundingClientRect();
				var b = bubble.getBoundingClientRect();
				var margin = 10;

				// Below the trigger, unless the viewport has no room left there.
				var below = r.bottom + margin + b.height <= window.innerHeight;
				bubble.setAttribute('data-placement', below ? 'bottom' : 'top');
				bubble.style.top = (below ? r.bottom + margin : r.top - margin - b.height) + 'px';

				// Centred on the trigger, then pulled back inside the viewport, the
				// arrow staying on the trigger rather than following the box.
				var left = r.left + r.width / 2 - b.width / 2;
				left = Math.max(12, Math.min(left, window.innerWidth - b.width - 12));
				bubble.style.left = left + 'px';
				bubble.style.setProperty('--arrow-x', (r.left + r.width / 2 - left) + 'px');
			};

			var build = function () {
				bubble = document.createElement('div');
				bubble.className = 'confirm-bubble';
				bubble.setAttribute('role', 'dialog');
				bubble.innerHTML =
					'<p class="confirm-bubble-message"></p>' +
					'<div class="confirm-bubble-actions">' +
					'<button type="button" class="btn btn-sm btn-outline-light" data-confirm-cancel></button>' +
					'<button type="button" class="btn btn-sm btn-primary" data-confirm-ok></button>' +
					'</div>';
				document.body.appendChild(bubble);

				bubble.querySelector('[data-confirm-cancel]').addEventListener('click', close);
				bubble.querySelector('[data-confirm-ok]').addEventListener('click', function () {
					var target = trigger;
					close();
					if (!target) return;

					var form = target.form || target.closest('form');
					if (!form) return;

					// requestSubmit carries the button, so its name and value reach
					// the server exactly as a real click would have sent them.
					if (form.requestSubmit) {
						form.requestSubmit(target);
						return;
					}
					var relay = document.createElement('input');
					relay.type = 'hidden';
					relay.name = target.name;
					relay.value = target.value;
					form.appendChild(relay);
					form.submit();
				});
			};

			document.addEventListener('click', function (event) {
				var target = event.target.closest('[data-confirm]');
				if (target) {
					event.preventDefault();
					if (!bubble) build();
					if (trigger === target) {
						close();
						return;
					}

					trigger = target;
					target.setAttribute('aria-expanded', 'true');

					// Moved inside the open dialog: its focus trap pulls focus back
					// out of anything sitting on the body. Being fixed, the bubble is
					// still placed against the viewport and escapes the clipping.
					var host = target.closest('.modal') || document.body;
					if (bubble.parentElement !== host) host.appendChild(bubble);
					bubble.querySelector('.confirm-bubble-message').textContent = target.getAttribute('data-confirm') || '';
					bubble.querySelector('[data-confirm-cancel]').textContent = text.cancel || 'Cancel';
					bubble.querySelector('[data-confirm-ok]').textContent = target.getAttribute('data-confirm-ok') || 'OK';

					// Laid out off-screen first: placing it needs its measured size.
					bubble.style.left = '-9999px';
					bubble.classList.add('is-open');
					place(target);
					// Deferred a frame: the dialog's focus trap runs on the same
					// event and would otherwise take the focus straight back.
					requestAnimationFrame(function () {
						if (trigger === target) bubble.querySelector('[data-confirm-cancel]').focus();
					});
					return;
				}

				if (bubble && trigger && !event.target.closest('.confirm-bubble')) {
					close();
				}
			});

			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && trigger) {
					// Bootstrap would otherwise close the whole dialog underneath.
					event.stopPropagation();
					close();
				}
			}, true);

			// Anything that moves the trigger takes the bubble away with it.
			window.addEventListener('resize', close);
			document.addEventListener('scroll', close, true);
		})();

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
