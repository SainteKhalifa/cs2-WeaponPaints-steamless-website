			<section class="access-panel panel narrow">
				<h1><?= h(t('access_title')) ?></h1>
				<p class="hint"><?= h(t('access_prompt')) ?></p>
				<?php if ($accessError) : ?>
					<div class="alert alert-danger"><?= h($accessError === 'rate_limited' ? sprintf(t('auth_rate_limited'), $accessRetryAfter) : t('access_invalid')) ?></div>
				<?php endif; ?>
				<form method="post" class="form-grid">
					<?= csrfInput() ?>
					<input type="hidden" name="action" value="verify_access">
					<label><?= h(t('access_password')) ?>
						<input class="form-control" type="password" name="access_password" autocomplete="current-password" required autofocus>
					</label>
					<button class="btn btn-primary" type="submit"><?= h(t('access_unlock')) ?></button>
				</form>
			</section>
