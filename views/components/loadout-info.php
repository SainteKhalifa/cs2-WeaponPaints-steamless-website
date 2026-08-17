			<header class="page-head">
				<div>
					<a class="back-link" href="index.php?action=list"><?= h(t('back_list')) ?></a>
					<h1><?= h(t('edit_preset')) ?></h1>
					<p><?= h(presetLabel($currentPreset)) ?> · <?= h($teams[$team]) ?></p>
				</div>
				<nav class="team-tabs">
					<a class="<?= $team === 2 ? 'active' : '' ?>" href="index.php?action=edit&id=<?= h($currentPreset['steamid']) ?>&team=2"><?= h($teams[2]) ?></a>
					<a class="<?= $team === 3 ? 'active' : '' ?>" href="index.php?action=edit&id=<?= h($currentPreset['steamid']) ?>&team=3"><?= h($teams[3]) ?></a>
				</nav>
			</header>

			<?php if (isset($_GET['saved'])) : ?><div class="alert alert-success"><?= h(t('saved_notice')) ?></div><?php endif; ?>
			<?php if (isset($_GET['imported'])) : ?><div class="alert alert-success"><?= h(t('inspect_imported')) ?></div><?php endif; ?>
			<?php if (strpos((string)($_GET['error'] ?? ''), 'inspect_') === 0) : ?>
				<?php $inspectErrorKey = 'inspect_error_' . substr((string)$_GET['error'], strlen('inspect_')); ?>
				<div class="alert alert-danger"><?= h(t($inspectErrorKey) !== $inspectErrorKey ? t($inspectErrorKey) : t('inspect_error_failed')) ?></div>
			<?php elseif (($_GET['error'] ?? '') === 'loadout_password') : ?>
				<div class="alert alert-danger"><?= h(t('loadout_password_required')) ?></div>
			<?php elseif (($_GET['error'] ?? '') === 'nickname') : ?>
				<div class="alert alert-danger"><?= h(t('nickname_too_long')) ?></div>
			<?php elseif (($_GET['error'] ?? '') === 'identity') : ?>
				<div class="alert alert-danger"><?= h(t('steamid_in_use')) ?></div>
			<?php elseif (($_GET['error'] ?? '') === 'steamid_data') : ?>
				<div class="alert alert-danger"><?= h(t('steamid_data_conflict')) ?></div>
			<?php elseif (isset($_GET['error'])) : ?>
				<div class="alert alert-danger"><?= h(t('save_failed')) ?></div>
			<?php endif; ?>

			<section class="panel loadout-info-panel">
				<div class="identity-panel-head">
					<div>
						<h2><?= h(t('basic_info')) ?></h2>
					</div>
					<span class="identity-status<?= loadoutHasPassword($currentPreset) ? ' active' : '' ?>" data-loadout-password-status data-enabled-label="<?= h(t('loadout_password_enabled')) ?>" data-disabled-label="<?= h(t('loadout_password_disabled')) ?>"><?= h(loadoutHasPassword($currentPreset) ? t('loadout_password_enabled') : t('loadout_password_disabled')) ?></span>
				</div>
				<form method="post" class="identity-form loadout-info-form">
					<?= csrfInput() ?>
					<input type="hidden" name="action" value="save_identity">
					<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
					<input type="hidden" name="team" value="<?= $team ?>">
					<div class="identity-main-fields">
						<label>Steam64 ID
							<input class="form-control" name="steamid" value="<?= h($currentPreset['steamid']) ?>" inputmode="numeric" pattern="\d{5,18}" minlength="5" maxlength="18" required>
						</label>
						<label><?= h(t('nickname')) ?>
							<input class="form-control" name="nickname" value="<?= h($currentPreset['nickname'] ?? '') ?>" maxlength="100">
						</label>
					</div>
					<div class="identity-loadout-password-settings">
						<div class="loadout-password-setting-copy">
							<strong><?= h(t('loadout_password_protection')) ?></strong>
							<small><?= h(t('loadout_password_optional_hint')) ?></small>
						</div>
						<label class="loadout-password-toggle form-check form-switch">
							<input class="form-check-input" type="checkbox" role="switch" name="enable_loadout_password" value="1" data-loadout-password-toggle <?= loadoutHasPassword($currentPreset) ? 'checked' : '' ?>>
							<span><?= h(t('enable_loadout_password')) ?></span>
						</label>
						<label class="loadout-password-input-wrap<?= loadoutHasPassword($currentPreset) ? '' : ' is-inactive' ?>" data-loadout-password-input-wrap>
							<span class="visually-hidden"><?= h(t('enter_loadout_password')) ?></span>
							<input class="form-control" type="password" name="loadout_password" autocomplete="one-time-code" placeholder="<?= h(loadoutHasPassword($currentPreset) ? t('loadout_password_change_placeholder') : t('loadout_password_set_placeholder')) ?>" data-loadout-password-input <?= loadoutHasPassword($currentPreset) ? '' : 'data-loadout-password-required-when-enabled' ?>>
						</label>
					</div>
					<div class="identity-form-actions">
						<button class="btn btn-primary" type="submit"><?= h(t('save')) ?></button>
					</div>
				</form>
			</section>
