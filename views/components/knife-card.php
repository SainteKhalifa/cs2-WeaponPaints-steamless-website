				<div class="skin-card featured loadout-card">
					<?php
					$actualKnife = $knifes[0];
					$actualKnifeKey = 0;
					if ($selectedKnife !== null) {
						foreach ($knifes as $knifeKey => $knife) {
							if ($selectedKnife['knife'] === $knife['weapon_name']) {
								$actualKnife = $knife;
								$actualKnifeKey = (int)$knifeKey;
								break;
							}
						}
					}
					$knifeSkinOptions = $actualKnifeKey > 0 ? ($skins[$actualKnifeKey] ?? []) : [];
					$selectedKnifeSkin = $selectedSkins[$actualKnifeKey] ?? null;
					$currentKnifePaintId = $selectedKnifeSkin ? (int)$selectedKnifeSkin['weapon_paint_id'] : 0;
					$currentKnifeIsFusion = $selectedKnifeSkin && isFusionPaint($actualKnifeKey, $currentKnifePaintId, $skins, $paintKits);
					$currentKnifeSkin = $actualKnifeKey > 0 && isset($knifeSkinOptions[$currentKnifePaintId])
						? $knifeSkinOptions[$currentKnifePaintId]
						: ($currentKnifeIsFusion
							? fusionSkinData($actualKnife, $paintKits[$currentKnifePaintId], $actualKnife['paint_name'] ?? '')
							: ($selectedKnifeSkin && $currentKnifePaintId > 0
								? unknownSkinData($actualKnife, $currentKnifePaintId, $actualKnife['paint_name'] ?? '')
								: $actualKnife));
					$currentKnifeWear = $selectedKnifeSkin['weapon_wear'] ?? 0.0;
					$currentKnifeSeed = $selectedKnifeSkin['weapon_seed'] ?? 0;
					$currentKnifeStatTrak = (int)($selectedKnifeSkin['weapon_stattrak'] ?? 0);
					$currentKnifeStatTrakCount = $currentKnifeStatTrak ? (int)($selectedKnifeSkin['weapon_stattrak_count'] ?? 0) : 0;
					$currentKnifeNameTag = $selectedKnifeSkin['weapon_nametag'] ?? null;
					$currentKnifeCanEdit = $actualKnifeKey > 0;
					$currentKnifeNameTagEnabled = $currentKnifeNameTag !== null && $currentKnifeNameTag !== '';
					$currentKnifeFinishBadge = paintKitFinishBadge($currentKnifePaintId);
					?>
					<?php if ($currentKnifeIsFusion || $currentKnifeFinishBadge || $currentKnifeNameTagEnabled || $currentKnifeStatTrak) : ?>
						<div class="card-status-badges">
							<?php if ($currentKnifeIsFusion) : ?><span class="fusion-badge"><?= h(t('skin_fusion')) ?></span><?php endif; ?>
							<?= paintKitFinishBadgeHtml($currentKnifePaintId) ?>
							<?php if ($currentKnifeNameTagEnabled) : ?><span class="nametag-badge"><?= h(t('name_tag')) ?></span><?php endif; ?>
							<?php if ($currentKnifeStatTrak) : ?><span class="stattrak-badge" data-stattrak-badge="<?= (int)$actualKnifeKey ?>">StatTrak™ <span data-stattrak-badge-count><?= h($currentKnifeStatTrakCount) ?></span></span><?php endif; ?>
						</div>
					<?php endif; ?>
					<div class="card-title-wrap">
						<span><?= h(t('knife')) ?></span>
						<h2><?= h($currentKnifeSkin['paint_name']) ?></h2>
					</div>
					<div class="skin-visual">
						<?php $knifePlaceholder = weaponPlaceholderImage($actualKnife['weapon_name'] ?? ''); ?>
						<?php if ($knifePlaceholder !== '') : ?>
							<img src="<?= h($knifePlaceholder) ?>" data-remote-src="<?= h($currentKnifeSkin['image_url'] ?? '') ?>" class="skin-image" alt="">
						<?php else : ?>
							<img src="<?= h($currentKnifeSkin['image_url']) ?>" class="skin-image" alt="">
						<?php endif; ?>
						<span class="pattern-badge"><?= h(t('pattern')) ?> <?= h($currentKnifeSeed) ?></span>
					</div>
					<div class="wear-meter" title="<?= h(t('wear_value') . ' ' . $currentKnifeWear) ?>">
						<span class="visually-hidden"><?= h(t('wear_value') . ' ' . $currentKnifeWear) ?></span>
						<div class="wear-value"><?= h(t('wear_value')) ?>: <?= h($currentKnifeWear) ?></div>
						<div class="wear-pointer-icon" style="left: <?= h(max(0, min(100, (float)$currentKnifeWear * 100))) ?>%"></div>
						<div class="progress">
							<div class="progress-bar progress-bar-fn" style="width: 7%" title="<?= h(t('wear_factory_new')) ?>"></div>
							<div class="progress-bar progress-bar-mw" style="width: 8%" title="<?= h(t('wear_minimal_wear')) ?>"></div>
							<div class="progress-bar progress-bar-ft" style="width: 23%" title="<?= h(t('wear_field_tested')) ?>"></div>
							<div class="progress-bar progress-bar-ww" style="width: 7%" title="<?= h(t('wear_well_worn')) ?>"></div>
							<div class="progress-bar progress-bar-bs" style="width: 55%" title="<?= h(t('wear_battle_scarred')) ?>"></div>
						</div>
					</div>
					<div class="settings-row">
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#knifeTypeModal">
							<?= h(t('choose_type')) ?>
						</button>
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#knifeSkinModal" <?= $actualKnifeKey === 0 ? 'disabled' : '' ?>>
							<?= h(t('choose_skin')) ?>
						</button>
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#knifeModal" <?= $currentKnifeCanEdit ? '' : 'disabled' ?>>
							<?= h(t('edit')) ?>
						</button>
						<?= inspectButton(
							$actualKnifeKey,
							inspectHexFromValues($actualKnifeKey, $currentKnifePaintId, $currentKnifeWear, $currentKnifeSeed, $currentKnifeStatTrak, $currentKnifeStatTrakCount, $currentKnifeNameTag, null, $selectedKnifeSkin['weapon_keychain'] ?? null),
							($actualKnife['weapon_name'] ?? '') . ' — ' . ($currentKnifeSkin['paint_name'] ?? '')
						) ?>
					</div>

					<form method="post" class="modal-form">
						<?= csrfInput() ?>
						<input type="hidden" name="action" value="save_skin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="modal fade skin-picker-modal" id="knifeTypeModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(t('choose_type_title')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<div class="skin-picker-grid">
											<?php foreach ($knifes as $knifeKey => $knife) : ?>
												<?php
												$knifeTypePlaceholder = weaponPlaceholderImage($knife['weapon_name'] ?? '');
												$knifeTypeImage = (string)($knife['image_url'] ?? '');
												?>
												<button type="submit" name="forma" value="knife-<?= (int)$knifeKey ?>" class="skin-result <?= $actualKnifeKey === (int)$knifeKey ? 'active' : '' ?>">
													<?php if ($knifeTypePlaceholder !== '') : ?>
														<img src="<?= h($knifeTypePlaceholder) ?>" data-picker-remote-src="<?= h($knifeTypeImage) ?>" alt="">
													<?php elseif ($knifeTypeImage !== '') : ?>
														<img src="<?= h($knifeTypeImage) ?>" alt="">
													<?php else : ?>
														<div class="empty-image"><?= h($knife['paint_name']) ?></div>
													<?php endif; ?>
													<span><?= h($knife['paint_name']) ?></span>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>

					<form method="post" class="modal-form">
						<?= csrfInput() ?>
						<input type="hidden" name="action" value="save_skin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="modal fade skin-picker-modal" id="knifeSkinModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(sprintf(t('choose_skin_for'), fusionTargetName($actualKnife, t('knife')))) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body picker-modal-body">
										<?php if ($actualKnifeKey === 0) : ?>
											<p class="hint"><?= h(t('choose_knife_hint')) ?></p>
										<?php else : ?>
											<div class="picker-search-bar">
												<input type="search" class="form-control picker-search" placeholder="<?= h(t('search_skin')) ?>" autocomplete="off" data-picker-search>
											</div>
										<div class="skin-picker-grid picker-results-scroll">
											<?php $knifeSkinPosition = 0; ?>
											<?php foreach ($knifeSkinOptions as $paintKey => $paint) : ?>
													<?php $knifeSkinImage = (string)($paint['image_url'] ?? ''); ?>
									<button type="submit" name="skin_forma" value="<?= (int)$actualKnifeKey ?>-<?= (int)$paintKey ?>" class="skin-result <?= $currentKnifePaintId === (int)$paintKey ? 'active' : '' ?>" data-picker-result data-search="<?= h(trim(($paint['paint_name'] ?? '') . ' ' . ($skinAliases[$actualKnifeKey][(int)$paintKey] ?? ''))) ?>">
														<?php if ($knifePlaceholder !== '') : ?>
															<img src="<?= h($knifePlaceholder) ?>" data-picker-remote-src="<?= h($knifeSkinImage) ?>" alt="">
														<?php elseif ($knifeSkinImage !== '') : ?>
															<img src="<?= h($knifeSkinImage) ?>" alt="">
												<?php else : ?>
													<div class="empty-image"><?= h($paint['paint_name']) ?></div>
												<?php endif; ?>
												<?= paintKitFinishBadgeHtml($paintKey) ?>
										<span><?= h($paint['paint_name']) ?></span>
											</button>
											<?php if ($knifeSkinPosition === 0 && skinFusionEnabled()) : ?>
												<button type="button" class="skin-result fusion-result" data-picker-result data-search="<?= h(t('fusion_finish_entry')) ?>" data-fusion-open data-fusion-defindex="<?= (int)$actualKnifeKey ?>" data-fusion-weapon="<?= h($actualKnife['weapon_name'] ?? '') ?>" data-fusion-target-name="<?= h(fusionTargetName($actualKnife, t('knife'))) ?>" data-fusion-official-paints="<?= h(implode(',', array_map('intval', array_keys($knifeSkinOptions)))) ?>">
													<?php if ($knifePlaceholder !== '') : ?>
														<img src="<?= h($knifePlaceholder) ?>" alt="">
													<?php else : ?>
													<div class="empty-image"><?= h(t('fusion_finish_entry')) ?></div>
													<?php endif; ?>
													<span class="fusion-badge fusion-picker-badge"><?= h(t('skin_fusion')) ?></span>
												<span><?= h(t('fusion_finish_entry')) ?></span>
												</button>
											<?php endif; ?>
											<?php $knifeSkinPosition++; ?>
										<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</form>

					<form method="post">
						<?= csrfInput() ?>
						<input type="hidden" name="action" value="save_skin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<input type="hidden" name="forma" value="<?= (int)$actualKnifeKey ?>-<?= (int)$currentKnifePaintId ?>">
						<div class="modal fade skin-edit-modal" id="knifeModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h($currentKnifeSkin['paint_name']) ?> <?= h(t('settings')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<?php if ($actualKnifeKey === 0) : ?>
											<p class="hint"><?= h(t('choose_knife_hint')) ?></p>
										<?php else : ?>
											<div class="row g-3">
												<div class="col-12 skin-param-grid">
													<div class="skin-param-row" data-skin-param-row>
														<label for="knifeWear"><?= h(t('wear_value')) ?></label>
														<div class="skin-param-control">
															<input type="range" min="0" max="1" step="0.01" value="<?= h($currentKnifeWear) ?>" data-skin-param-range>
													<input id="knifeWear" type="number" min="0" max="1" step="0.01" value="<?= h(skinWearDisplayValue($currentKnifeWear)) ?>" class="form-control" name="wear" data-skin-param-number data-max-decimals="8">
														</div>
													</div>
													<div class="skin-param-row" data-skin-param-row>
														<label for="knifeSeed"><?= h(t('pattern')) ?></label>
														<div class="skin-param-control">
															<input type="range" min="0" max="1000" step="1" value="<?= h($currentKnifeSeed) ?>" data-skin-param-range>
															<input id="knifeSeed" type="number" min="0" max="1000" step="1" value="<?= h($currentKnifeSeed) ?>" class="form-control" name="seed" data-skin-param-number>
														</div>
													</div>
												</div>
												<div class="col-12 weapon-option-grid">
												<div class="nametag-row weapon-option-card">
													<input type="hidden" name="nametag_present" value="1">
													<label class="check-line">
														<input type="checkbox" name="nametag_enabled" value="1" data-nametag-toggle <?= $currentKnifeNameTagEnabled ? 'checked' : '' ?>>
														<span class="nametag-label"><?= h(t('name_tag')) ?></span>
													</label>
											<input type="text" name="weapon_nametag" value="<?= h($currentKnifeNameTag ?? '') ?>" maxlength="20" autocomplete="off" autocapitalize="off" spellcheck="false" class="form-control nametag-input<?= $currentKnifeNameTagEnabled ? '' : ' is-inactive' ?>" data-nametag-input <?= $currentKnifeNameTagEnabled ? '' : 'disabled' ?>>
												</div>
												<div class="stattrak-row weapon-option-card">
	<label class="check-line">
		<input type="checkbox" name="stattrak" value="1" data-stattrak-toggle <?= $currentKnifeStatTrak ? 'checked' : '' ?>>
		<span class="stattrak-label">StatTrak™</span>
	</label>
	<input type="number" name="weapon_stattrak_count" value="<?= h($currentKnifeStatTrakCount) ?>" min="0" max="999999" step="1" class="form-control stattrak-input<?= $currentKnifeStatTrak ? '' : ' is-inactive' ?>" data-stattrak-input <?= $currentKnifeStatTrak ? '' : 'disabled' ?> <?= stattrakCountEditable() ? "" : "readonly" ?> title="<?= h(stattrakCountEditable() ? "" : t("stattrak_count_locked")) ?>">
	<button type="submit" name="stattrak_reset" value="1" class="btn btn-sm btn-outline-light stattrak-reset" data-confirm="<?= h(t("stattrak_reset_confirm")) ?>" data-confirm-ok="<?= h(t("stattrak_reset")) ?>" aria-expanded="false" title="<?= h(t("stattrak_reset_title")) ?>"><?= h(t("stattrak_reset")) ?></button>
												</div>
</div>
											</div>
										<?php endif; ?>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
										<?php if ($actualKnifeKey > 0) : ?><button type="submit" class="btn btn-primary"><?= h(t('save')) ?></button><?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
