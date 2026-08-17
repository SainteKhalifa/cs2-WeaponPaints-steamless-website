	<div class="modal fade sticker-picker-modal" id="stickerPickerModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?= h(t('choose_sticker')) ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
				</div>
				<div class="modal-body picker-modal-body">
					<div class="picker-search-bar">
						<input type="search" class="form-control sticker-search" placeholder="<?= h(t('search_sticker')) ?>" autocomplete="off">
					</div>
					<div class="sticker-picker-grid picker-results-scroll" data-sticker-results></div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade sticker-advanced-modal" id="stickerAdvancedModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<form method="post" class="modal-content" data-sticker-advanced-form>
				<?= csrfInput() ?>
				<input type="hidden" name="action" value="save_sticker_slot">
				<input type="hidden" name="id" value="<?= h($currentPreset['steamid'] ?? '') ?>" data-sticker-advanced-id>
				<input type="hidden" name="team" value="<?= h((string)($team ?? 1)) ?>" data-sticker-advanced-team>
				<input type="hidden" name="weapon_defindex" value="" data-sticker-advanced-defindex>
				<input type="hidden" name="sticker_slot" value="" data-sticker-advanced-slot>
				<div class="modal-header">
					<div>
						<h5 class="modal-title" data-sticker-advanced-title><?= h(t('sticker_slot_settings')) ?></h5>
						<div class="sticker-advanced-subtitle" data-sticker-advanced-name></div>
					</div>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
				</div>
				<div class="modal-body sticker-advanced-body">
					<?php $stickerParams = [
						'wear' => [t('sticker_wear'), '0', '1', '0.01', '0.00'],
						'x' => [t('sticker_x'), '-1', '1', '0.01', '0.00'],
						'y' => [t('sticker_y'), '-1', '1', '0.01', '0.00'],
						'scale' => [t('sticker_scale'), '0.2', '5', '0.01', '1.00'],
						'rotation' => [t('sticker_rotation'), '0', '360', '1', '0'],
					]; ?>
					<?php foreach ($stickerParams as $paramKey => $paramConfig) : ?>
						<div class="sticker-advanced-row" data-sticker-param="<?= h($paramKey) ?>">
							<label><?= h($paramConfig[0]) ?></label>
							<div class="sticker-advanced-controls">
								<input type="range" min="<?= h($paramConfig[1]) ?>" max="<?= h($paramConfig[2]) ?>" step="<?= h($paramConfig[3]) ?>" value="<?= h($paramConfig[4]) ?>" data-sticker-param-range>
								<input type="number" name="sticker_<?= h($paramKey) ?>" min="<?= h($paramConfig[1]) ?>" max="<?= h($paramConfig[2]) ?>" step="<?= h($paramConfig[3]) ?>" value="<?= h($paramConfig[4]) ?>" class="form-control" data-sticker-param-number>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-light" data-sticker-advanced-reset><?= h(t('reset')) ?></button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
					<button type="submit" class="btn btn-primary"><?= h(t('save')) ?></button>
				</div>
			</form>
		</div>
	</div>
	<div class="modal fade keychain-picker-modal" id="keychainPickerModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?= h(t('choose_keychain')) ?></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
				</div>
				<div class="modal-body picker-modal-body">
					<div class="picker-search-bar">
						<input type="search" class="form-control keychain-search" placeholder="<?= h(t('search_keychain')) ?>" autocomplete="off">
					</div>
					<div class="keychain-picker-grid picker-results-scroll" data-keychain-results></div>
				</div>
			</div>
		</div>
	</div>
	<?php if (skinFusionEnabled() && $action === 'edit' && $currentPreset) : ?>
		<div class="modal fade fusion-picker-modal" id="fusionPickerModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 class="modal-title" data-fusion-picker-title><?= h(t('choose_fusion_finish')) ?></h5>
							<div class="modal-subtitle"><?= h(t('fusion_experimental_hint')) ?></div>
						</div>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
					</div>
					<div class="modal-body picker-modal-body">
						<div class="picker-search-bar">
							<input type="search" class="form-control fusion-search" placeholder="<?= h(t('search_fusion_finish')) ?>" autocomplete="off">
						</div>
						<div class="fusion-picker-grid picker-results-scroll" data-fusion-results></div>
					</div>
				</div>
			</div>
		</div>
		<div class="modal fade fusion-source-modal" id="fusionSourceModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<div>
							<h5 class="modal-title"><?= h(t('fusion_sources_title')) ?></h5>
							<div class="modal-subtitle" data-fusion-source-paint-name></div>
						</div>
					</div>
					<div class="modal-body">
						<div class="fusion-source-grid" data-fusion-source-results></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('back')) ?></button>
					</div>
				</div>
			</div>
		</div>
		<form method="post" id="fusionSkinForm" class="d-none">
			<?= csrfInput() ?>
			<input type="hidden" name="action" value="save_skin">
			<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
			<input type="hidden" name="team" value="<?= h((string)$team) ?>">
			<input type="hidden" name="skin_forma" value="" data-fusion-forma>
		</form>
	<?php endif; ?>
	<?php if ($accessGranted) : ?>
		<div class="modal fade" id="loadoutPasswordModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-sm">
				<form method="post" class="modal-content">
					<?= csrfInput() ?>
					<input type="hidden" name="action" value="verify_loadout_password">
					<input type="hidden" name="id" value="" data-loadout-password-id-input>
					<input type="hidden" name="team" value="1" data-loadout-password-team-input>
					<div class="modal-header">
						<div>
							<h5 class="modal-title"><?= h(t('enter_loadout_password')) ?></h5>
							<div class="modal-subtitle" data-loadout-password-label></div>
						</div>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('cancel')) ?>"></button>
					</div>
					<div class="modal-body form-grid">
						<p class="hint"><?= h(t('loadout_password_prompt')) ?></p>
						<div class="alert alert-danger d-none" data-loadout-password-error><?= h(isset($_GET['loadout_password_rate_limited']) ? sprintf(t('auth_rate_limited'), max(1, (int)($_GET['retry_after'] ?? 1))) : t('loadout_password_incorrect')) ?></div>
						<label><?= h(t('enter_loadout_password')) ?>
							<input class="form-control" type="password" name="loadout_password" autocomplete="one-time-code" required data-loadout-password-modal-input>
						</label>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
						<button type="submit" class="btn btn-primary"><?= h(t('edit')) ?></button>
					</div>
				</form>
			</div>
		</div>

		<div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-sm">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title"><?= h(isAdmin() ? t('admin_enabled') : t('admin_login')) ?></h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('cancel')) ?>"></button>
					</div>
					<div class="modal-body">
						<?php if (adminPassword() === '') : ?>
							<div class="alert alert-info mb-0"><?= h(t('admin_disabled')) ?></div>
						<?php elseif (isAdmin()) : ?>
							<p class="hint"><?= h(t('admin_enabled')) ?></p>
						<?php else : ?>
							<?php if ($adminError) : ?>
								<div class="alert alert-danger"><?= h($adminError === 'rate_limited' ? sprintf(t('auth_rate_limited'), max(1, $adminRetryAfter)) : t('admin_invalid')) ?></div>
							<?php endif; ?>
							<form method="post" class="form-grid" id="adminLoginForm">
								<?= csrfInput() ?>
								<input type="hidden" name="action" value="admin_login">
								<input type="hidden" name="return_to" value="<?= h($returnTo) ?>">
								<label><?= h(t('admin_password')) ?>
									<input class="form-control" type="password" name="admin_password" autocomplete="current-password" required>
								</label>
							</form>
						<?php endif; ?>
					</div>
					<?php if (adminPassword() !== '') : ?>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('back')) ?></button>
							<?php if (isAdmin()) : ?>
								<form method="post">
									<?= csrfInput() ?>
									<input type="hidden" name="action" value="admin_logout">
									<input type="hidden" name="return_to" value="<?= h($returnTo) ?>">
									<button class="btn btn-outline-danger" type="submit"><?= h(t('admin_exit')) ?></button>
								</form>
							<?php else : ?>
								<button class="btn btn-primary" type="submit" form="adminLoginForm"><?= h(t('admin_enter')) ?></button>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="modal fade inspect-modal" id="inspectModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<form method="post" class="modal-content">
				<?= csrfInput() ?>
				<input type="hidden" name="action" value="import_inspect_link">
				<input type="hidden" name="id" value="<?= h($currentPreset['steamid'] ?? '') ?>">
				<input type="hidden" name="team" value="<?= h((string)($team ?? 2)) ?>">
				<input type="hidden" name="weapon_defindex" value="" data-inspect-defindex-field>
				<div class="modal-header">
					<div>
						<h5 class="modal-title"><?= h(t('inspect_title')) ?></h5>
						<div class="inspect-subtitle" data-inspect-label></div>
					</div>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
				</div>
				<div class="modal-body">
					<ol class="inspect-steps">
						<li><?= h(t('inspect_step_open')) ?></li>
						<li><?= h(t('inspect_step_place')) ?></li>
						<li><?= h(t('inspect_step_paste')) ?></li>
					</ol>
					<div class="inspect-actions">
						<a class="btn btn-primary" href="#" target="_blank" rel="noopener noreferrer" data-inspect-open-link><?= h(t('inspect_open')) ?></a>
						<button type="button" class="btn btn-outline-light" data-inspect-paste hidden><?= h(t('inspect_paste')) ?></button>
					</div>
					<input type="text" name="inspect_link" class="form-control inspect-input" placeholder="<?= h(t('inspect_import_placeholder')) ?>" autocomplete="off" spellcheck="false" required data-inspect-input>
					<label class="inspect-placement">
						<input type="checkbox" name="inspect_keep_placement" value="1">
						<span>
							<strong><?= h(t('inspect_keep_placement')) ?></strong>
							<small><?= h(t('inspect_keep_placement_hint')) ?></small>
						</span>
					</label>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
					<button type="submit" class="btn btn-primary"><?= h(t('inspect_import_apply')) ?></button>
				</div>
			</form>
		</div>
	</div>
