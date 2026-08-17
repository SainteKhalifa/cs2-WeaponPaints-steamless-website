(function (app) {
	'use strict';
	var appConfig = app.config || {};
	var appText = appConfig.text || {};
	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape') return;
		var fusion = app.fusion || {};
		var stickers = app.stickers || {};
		var keychains = app.keychains || {};
		var targets = [
			[fusion.sourceEl, fusion.sourceModal],
			[stickers.advancedEl, stickers.advancedModal],
			[stickers.pickerEl, stickers.picker],
			[keychains.pickerEl, keychains.picker],
			[fusion.pickerEl, fusion.picker]
		];
		for (var index = 0; index < targets.length; index++) {
			if (!targets[index][0] || !targets[index][0].classList.contains('show')) continue;
			event.preventDefault();
			event.stopPropagation();
			if (targets[index][1]) targets[index][1].hide();
			return;
		}
	}, true);
})(window.cs2App = window.cs2App || {});
