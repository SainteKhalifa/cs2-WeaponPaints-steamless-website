			<header class="page-head">
				<div>
					<a class="back-link" href="index.php"><?= h(t('back_home')) ?></a>
					<h1><?= h(t('select_preset')) ?></h1>
				</div>
				<a class="btn btn-primary" href="index.php?action=new"><?= h(t('new_preset')) ?></a>
			</header>

			<?php if (($_GET['notice'] ?? '') === 'updated_existing') : ?>
				<div class="alert alert-info"><?= h(t('updated_notice')) ?></div>
			<?php endif; ?>

			<?php if (!$presets) : ?>
				<section class="panel"><?= h(t('empty_presets')) ?></section>
			<?php endif; ?>

			<div class="preset-list">
				<?php foreach ($presets as $preset) : ?>
					<article class="preset-card">
						<div class="preset-card-body">
							<strong><?= h(presetLabel($preset)) ?></strong>
							<span><?= h($preset['steamid']) ?></span>
							<?php if (loadoutHasPassword($preset)) : ?>
								<div class="loadout-password-label" title="<?= h(t('loadout_password_enabled')) ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
									<?= h(t('loadout_password_label')) ?>
								</div>
							<?php endif; ?>
						</div>
						<div class="preset-actions">
							<?php if (canEditPreset($preset)) : ?>
								<a class="btn btn-outline-light" href="<?= h(editUrl($preset, 1)) ?>"><?= h(t('edit')) ?></a>
							<?php else : ?>
								<button class="btn btn-outline-light" type="button" data-bs-toggle="modal" data-bs-target="#loadoutPasswordModal" data-loadout-password-id="<?= h($preset['steamid']) ?>" data-loadout-password-label="<?= h(presetLabel($preset)) ?>" data-loadout-password-team="1"><?= h(t('edit')) ?></button>
							<?php endif; ?>
							<form method="post" onsubmit="return confirm(<?= h(json_encode(t('delete_confirm'), JSON_UNESCAPED_UNICODE)) ?>);">
								<?= csrfInput() ?>
								<input type="hidden" name="action" value="delete_preset">
								<input type="hidden" name="id" value="<?= h($preset['steamid']) ?>">
								<?php if (canDeletePreset($preset)) : ?>
									<button class="btn btn-outline-danger" type="submit"><?= h(t('delete')) ?></button>
								<?php else : ?>
									<span class="delete-tooltip-wrap" tabindex="0" title="<?= h(t('delete_permission_hint')) ?>">
										<button class="btn btn-outline-danger" type="button" disabled><?= h(t('delete')) ?></button>
									</span>
								<?php endif; ?>
							</form>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
