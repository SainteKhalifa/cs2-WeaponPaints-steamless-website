(function (app) {
	'use strict';
	var appConfig = window.cs2AppConfig || {};
	var appText = appConfig.text || {};
	window.cs2CsrfToken = appConfig.csrfToken || '';
	window.cs2StickerDataUrl = appConfig.stickerDataUrl || '';
	window.cs2StickerAliasDataUrl = appConfig.stickerAliasDataUrl || '';
	window.cs2KeychainDataUrl = appConfig.keychainDataUrl || '';
	window.cs2KeychainAliasDataUrl = appConfig.keychainAliasDataUrl || '';
	window.cs2PaintKitDataUrl = appConfig.paintKitDataUrl || '';
	window.cs2PaintKitAliasDataUrl = appConfig.paintKitAliasDataUrl || '';
	window.cs2PaintKitFinishBadges = appConfig.paintKitFinishBadges || {};
			var themeToggle = document.querySelector('[data-theme-toggle]');
			if (themeToggle) {
				var themeTransitionTimer = null;
				var syncThemeToggle = function () {
					var currentTheme = document.documentElement.dataset.bsTheme === 'light' ? 'light' : 'dark';
					var label = currentTheme === 'dark' ? themeToggle.dataset.lightLabel : themeToggle.dataset.darkLabel;
					themeToggle.setAttribute('aria-label', label);
					themeToggle.setAttribute('title', label);
					themeToggle.setAttribute('aria-pressed', currentTheme === 'light' ? 'true' : 'false');
				};
				themeToggle.addEventListener('click', function () {
					var nextTheme = document.documentElement.dataset.bsTheme === 'light' ? 'dark' : 'light';
					var applyTheme = function () {
						document.documentElement.dataset.bsTheme = nextTheme;
						try {
							window.localStorage.setItem('cs2_wp_theme', nextTheme);
						} catch (error) {
							// The current page still switches even when persistence is unavailable.
						}
						syncThemeToggle();
					};
					if (typeof document.startViewTransition === 'function') {
						document.startViewTransition(applyTheme);
						return;
					}
					document.documentElement.classList.add('theme-transitioning');
					window.requestAnimationFrame(applyTheme);
					if (themeTransitionTimer !== null) window.clearTimeout(themeTransitionTimer);
					themeTransitionTimer = window.setTimeout(function () {
						document.documentElement.classList.remove('theme-transitioning');
						themeTransitionTimer = null;
					}, 1000);
				});
				syncThemeToggle();
			}

			var indicatorFitFrame = null;
			var fitStatusIndicatorRows = function () {
				document.querySelectorAll('.card-status-badges, .fusion-result-indicators').forEach(function (row) {
					row.style.transform = '';
					var parent = row.parentElement;
					if (!parent || parent.clientWidth <= 0) return;
					var rightInset = parseFloat(window.getComputedStyle(row).right) || 0;
					var availableWidth = Math.max(0, parent.clientWidth - (rightInset * 2));
					var requiredWidth = row.scrollWidth;
					if (requiredWidth > availableWidth && availableWidth > 0) {
						row.style.transform = 'scale(' + (availableWidth / requiredWidth) + ')';
					}
				});
			};
			var scheduleStatusIndicatorFit = function () {
				if (indicatorFitFrame !== null) window.cancelAnimationFrame(indicatorFitFrame);
				indicatorFitFrame = window.requestAnimationFrame(function () {
					indicatorFitFrame = null;
					fitStatusIndicatorRows();
				});
			};
			scheduleStatusIndicatorFit();
			window.addEventListener('resize', scheduleStatusIndicatorFit);
			if (document.fonts && document.fonts.ready) {
				document.fonts.ready.then(scheduleStatusIndicatorFit);
			}
			document.querySelectorAll('[data-loadout-password-toggle]').forEach(function (toggle) {
				var form = toggle.closest('form');
				var wrap = form ? form.querySelector('[data-loadout-password-input-wrap]') : null;
				var input = form ? form.querySelector('[data-loadout-password-input]') : null;
				var panel = form ? form.closest('.loadout-info-panel') : null;
				var status = panel ? panel.querySelector('[data-loadout-password-status]') : null;
				var sync = function () {
					if (wrap) wrap.classList.toggle('is-inactive', !toggle.checked);
					if (input) {
						input.disabled = !toggle.checked;
						input.required = toggle.checked && input.hasAttribute('data-loadout-password-required-when-enabled');
					}
					if (status) {
						status.classList.toggle('active', toggle.checked);
						status.textContent = toggle.checked ? status.dataset.enabledLabel : status.dataset.disabledLabel;
					}
				};
				toggle.addEventListener('change', sync);
				sync();
			});

			var loadoutPasswordModalEl = document.getElementById('loadoutPasswordModal');
			if (loadoutPasswordModalEl) {
				loadoutPasswordModalEl.addEventListener('show.bs.modal', function (event) {
					var trigger = event.relatedTarget;
					if (!trigger) return;
					loadoutPasswordModalEl.querySelector('[data-loadout-password-id-input]').value = trigger.dataset.loadoutPasswordId || '';
					loadoutPasswordModalEl.querySelector('[data-loadout-password-team-input]').value = trigger.dataset.loadoutPasswordTeam || '1';
					loadoutPasswordModalEl.querySelector('[data-loadout-password-label]').textContent = trigger.dataset.loadoutPasswordLabel || '';
					loadoutPasswordModalEl.querySelector('[data-loadout-password-error]').classList.toggle('d-none', trigger.dataset.loadoutPasswordError !== '1');
				});
				loadoutPasswordModalEl.addEventListener('shown.bs.modal', function () {
					var input = loadoutPasswordModalEl.querySelector('[data-loadout-password-modal-input]');
					if (input) input.focus();
				});
				loadoutPasswordModalEl.addEventListener('hidden.bs.modal', function () {
					var input = loadoutPasswordModalEl.querySelector('[data-loadout-password-modal-input]');
					if (input) input.value = '';
				});
				var requestedLoadoutPasswordId = appConfig.requestedLoadoutPasswordId || '';
				if (requestedLoadoutPasswordId) {
					var trigger = document.querySelector('[data-loadout-password-id="' + CSS.escape(requestedLoadoutPasswordId) + '"]');
					if (trigger) {
						trigger.dataset.loadoutPasswordTeam = String(appConfig.requestedLoadoutPasswordTeam || '1');
						if (appConfig.hasLoadoutPasswordError === true) trigger.dataset.loadoutPasswordError = '1';
						bootstrap.Modal.getOrCreateInstance(loadoutPasswordModalEl).show(trigger);
					}
				}
			}

			if (appConfig.showAdminError) {
			var adminModalEl = document.getElementById('adminModal');
			if (adminModalEl) bootstrap.Modal.getOrCreateInstance(adminModalEl).show();
			}

			var params = new URLSearchParams(location.search);
			var key = 'cs2_wp_scroll_' + location.pathname + '_' + (params.get('action') || '') + '_' + (params.get('id') || '') + '_' + (params.get('team') || '');
			window.rememberScrollPosition = function () {
				sessionStorage.setItem(key, String(window.scrollY || window.pageYOffset || 0));
			};
			var savedY = sessionStorage.getItem(key);
			if (savedY !== null) {
				sessionStorage.removeItem(key);
				if ('scrollRestoration' in history) {
					history.scrollRestoration = 'manual';
				}
				var html = document.documentElement;
				var previousScrollBehavior = html.style.scrollBehavior;
				html.style.scrollBehavior = 'auto';
				window.scrollTo(0, parseInt(savedY, 10) || 0);
				requestAnimationFrame(function () {
					html.style.scrollBehavior = previousScrollBehavior;
				});
			}

			document.addEventListener('submit', function () {
				window.rememberScrollPosition();
			}, true);

	app.scheduleStatusIndicatorFit = scheduleStatusIndicatorFit;


	app.fetchJson = function (url) {
		if (!url) return Promise.resolve([]);
		return fetch(url, { cache: 'no-cache' }).then(function (response) {
			if (!response.ok) throw new Error('HTTP ' + response.status + ': ' + url);
			return response.json();
		}).then(function (payload) {
			if (!Array.isArray(payload)) throw new Error('Invalid JSON payload: ' + url);
			return payload;
		});
	};
	app.fetchOptionalJson = function (url) {
		return app.fetchJson(url).catch(function (error) {
			if (window.console && console.warn) console.warn(error);
			return [];
		});
	};
	app.config = appConfig;

})(window.cs2App = window.cs2App || {});
