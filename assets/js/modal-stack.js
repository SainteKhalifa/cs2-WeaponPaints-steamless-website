(function (app) {
	'use strict';
	var appConfig = app.config || {};
	var appText = appConfig.text || {};
	var activeStickerUnderlay = null;
			var setStickerUnderlay = function (modal) {
				if (activeStickerUnderlay && activeStickerUnderlay !== modal) {
					activeStickerUnderlay.classList.remove('sticker-underlay-active');
				}
				activeStickerUnderlay = modal || null;
				if (activeStickerUnderlay) {
					activeStickerUnderlay.classList.add('sticker-underlay-active');
				}
			};
			var modalPageOriginalPaddingRight = null;
			var modalPageLockedPaddingRight = null;
			var syncModalPageScrollLock = function () {
				var hasOpenModal = !!document.querySelector('.modal.show');
				document.documentElement.classList.toggle('modal-stack-open', hasOpenModal);
				document.body.classList.toggle('modal-stack-open', hasOpenModal);
				if (hasOpenModal) {
					document.body.classList.add('modal-open');
					if (modalPageLockedPaddingRight === null) {
						modalPageLockedPaddingRight = document.body.style.paddingRight;
					} else {
						document.body.style.paddingRight = modalPageLockedPaddingRight;
					}
					return;
				}
				if (modalPageOriginalPaddingRight !== null) {
					document.body.style.paddingRight = modalPageOriginalPaddingRight;
				}
				modalPageOriginalPaddingRight = null;
				modalPageLockedPaddingRight = null;
			};
			document.addEventListener('show.bs.modal', function () {
				if (!document.querySelector('.modal.show')) {
					modalPageOriginalPaddingRight = document.body.style.paddingRight;
					modalPageLockedPaddingRight = null;
				}
			}, true);
			document.addEventListener('shown.bs.modal', syncModalPageScrollLock, true);
			document.addEventListener('hidden.bs.modal', function () {
				syncModalPageScrollLock();
				window.requestAnimationFrame(syncModalPageScrollLock);
			}, true);

			var markStickerBackdrop = function () {
				var backdrops = document.querySelectorAll('.modal-backdrop');
				var backdrop = backdrops.length ? backdrops[backdrops.length - 1] : null;
				if (backdrop) {
					backdrop.classList.add('sticker-picker-backdrop');
				}
			};

	app.setModalUnderlay = setStickerUnderlay;
	app.markPickerBackdrop = markStickerBackdrop;

})(window.cs2App = window.cs2App || {});
