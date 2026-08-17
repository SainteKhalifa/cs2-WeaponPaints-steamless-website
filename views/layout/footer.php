		<footer class="site-footer">Copyright © 2026 wtf729 - All rights reserved</footer>
	</main>
	<script>
		window.cs2AppConfig = <?= json_encode($clientConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
	</script>
	<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/../../assets/js/app.js') ?>"></script>
	<script src="assets/js/image-loader.js?v=<?= filemtime(__DIR__ . '/../../assets/js/image-loader.js') ?>"></script>
	<script src="assets/js/pickers.js?v=<?= filemtime(__DIR__ . '/../../assets/js/pickers.js') ?>"></script>
	<script src="assets/js/modal-stack.js?v=<?= filemtime(__DIR__ . '/../../assets/js/modal-stack.js') ?>"></script>
	<script src="assets/js/fusion.js?v=<?= filemtime(__DIR__ . '/../../assets/js/fusion.js') ?>"></script>
	<script src="assets/js/stickers.js?v=<?= filemtime(__DIR__ . '/../../assets/js/stickers.js') ?>"></script>
	<script src="assets/js/keychains.js?v=<?= filemtime(__DIR__ . '/../../assets/js/keychains.js') ?>"></script>
	<script src="assets/js/modal-escape.js?v=<?= filemtime(__DIR__ . '/../../assets/js/modal-escape.js') ?>"></script>
	<script src="assets/js/forms.js?v=<?= filemtime(__DIR__ . '/../../assets/js/forms.js') ?>"></script>
	<script src="assets/js/inspect.js?v=<?= filemtime(__DIR__ . '/../../assets/js/inspect.js') ?>"></script>
</body>
</html>
