				<?php foreach ($weapons as $defindex => $default) : ?>
					<?php
					if (in_array((int)$defindex, knifeDefindexes($knifes), true) || in_array((int)$defindex, gloveDefindexes($gloves), true)) {
						continue;
					}
					$hasSkin = array_key_exists($defindex, $selectedSkins);
					$currentPaintId = $hasSkin ? (int)$selectedSkins[$defindex]['weapon_paint_id'] : 0;
					$usesInventorySkin = $currentPaintId === 0;
					$currentIsFusion = $hasSkin && isFusionPaint($defindex, $currentPaintId, $skins, $paintKits);
					$currentSkin = $hasSkin && isset($skins[$defindex][$currentPaintId])
						? $skins[$defindex][$currentPaintId]
						: ($currentIsFusion
							? fusionSkinData($default, $paintKits[$currentPaintId], $default['weapon_name'] ?? '')
							: ($hasSkin && $currentPaintId > 0
								? unknownSkinData($default, $currentPaintId, $default['weapon_name'] ?? '')
								: $default));
					$initialWearValue = $hasSkin ? $selectedSkins[$defindex]['weapon_wear'] : 0.0;
					$initialSeedValue = $hasSkin ? $selectedSkins[$defindex]['weapon_seed'] : 0;
					$initialStatTrakValue = $hasSkin ? (int)($selectedSkins[$defindex]['weapon_stattrak'] ?? 0) : 0;
					$initialStatTrakCountValue = $initialStatTrakValue ? (int)($selectedSkins[$defindex]['weapon_stattrak_count'] ?? 0) : 0;
					$initialNameTagValue = $hasSkin ? ($selectedSkins[$defindex]['weapon_nametag'] ?? null) : null;
					$initialStickerValues = $hasSkin ? stickerValuesFromRow($selectedSkins[$defindex]) : defaultStickerValues();
					$initialKeychainValue = $hasSkin ? ($selectedSkins[$defindex]['weapon_keychain'] ?? defaultKeychainValue()) : defaultKeychainValue();
					$initialKeychainId = keychainIdFromValue($initialKeychainValue);
					$initialKeychain = $keychains[$initialKeychainId] ?? unknownItemData($initialKeychainId);
					$initialKeychainParts = keychainValueParts($initialKeychainValue);
					$allowsCustomization = supportsWeaponCustomization((int)$defindex);
					$currentSkinCanEdit = $currentPaintId > 0;
					$stickerSlotTotal = $allowsCustomization ? stickerSlotCount((int)$defindex) : 0;
					$initialNameTagEnabled = $initialNameTagValue !== null && $initialNameTagValue !== '';
					$currentFinishBadge = paintKitFinishBadge($currentPaintId);
					$initialStickerIds = array_map('stickerIdFromValue', $initialStickerValues);
					$modalId = "weaponModal{$defindex}";
					$skinPickerId = "skinPicker{$defindex}";
					?>
					<div class="skin-card weapon-card">
						<?php if ($currentIsFusion || $currentFinishBadge || $initialNameTagEnabled || $initialStatTrakValue) : ?>
							<div class="card-status-badges">
								<?php if ($currentIsFusion) : ?><span class="fusion-badge"><?= h(t('skin_fusion')) ?></span><?php endif; ?>
								<?= paintKitFinishBadgeHtml($currentPaintId) ?>
								<?php if ($initialNameTagEnabled) : ?><span class="nametag-badge"><?= h(t('name_tag')) ?></span><?php endif; ?>
								<?php if ($initialStatTrakValue) : ?><span class="stattrak-badge" data-stattrak-badge="<?= (int)$defindex ?>">StatTrak™ <span data-stattrak-badge-count><?= h($initialStatTrakCountValue) ?></span></span><?php endif; ?>
							</div>
						<?php endif; ?>
						<div class="card-title-wrap">
							<span><?= h($default['weapon_name']) ?></span>
							<h2><?= h($currentSkin['paint_name']) ?></h2>
						</div>
						<div class="skin-visual">
							<?php $weaponPlaceholder = weaponPlaceholderImage($default['weapon_name'] ?? ''); ?>
							<?php if ($weaponPlaceholder !== '') : ?>
								<img src="<?= h($weaponPlaceholder) ?>" data-remote-src="<?= h($currentSkin['image_url'] ?? '') ?>" class="skin-image" alt="">
							<?php else : ?>
								<img src="<?= h($currentSkin['image_url']) ?>" class="skin-image" alt="">
							<?php endif; ?>
							<span class="pattern-badge"><?= h(t('pattern')) ?> <?= $usesInventorySkin ? '?' : h($initialSeedValue) ?></span>
						</div>
						<form method="post">
							<?= csrfInput() ?>
							<input type="hidden" name="action" value="save_skin">
							<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
							<input type="hidden" name="team" value="<?= $team ?>">
							<input type="hidden" name="forma" value="<?= (int)$defindex ?>-<?= (int)$currentPaintId ?>">
							<?php $cardStickerIds = array_values(array_filter(array_slice($initialStickerIds, 0, $stickerSlotTotal), static fn($stickerId) => (int)$stickerId > 0)); ?>
							<?php if ($allowsCustomization && ($cardStickerIds || $initialKeychainId > 0)) : ?>
								<div class="card-stickers" aria-label="<?= h(t('stickers')) ?>">
									<?php foreach ($cardStickerIds as $cardStickerId) : ?>
										<?php $cardSticker = $stickers[(int)$cardStickerId] ?? unknownItemData((int)$cardStickerId); ?>
										<img src="img/skins/sticker.png" data-remote-src="<?= h($cardSticker['image'] ?? '') ?>" alt="<?= h($cardSticker['name'] ?? '') ?>" title="<?= h($cardSticker['name'] ?? '') ?>">
									<?php endforeach; ?>
									<?php if ($initialKeychainId > 0) : ?>
										<img class="card-keychain-preview" src="img/skins/keychain.png" data-remote-src="<?= h($initialKeychain['image'] ?? '') ?>" alt="<?= h($initialKeychain['name'] ?? t('keychain')) ?>" title="<?= h($initialKeychain['name'] ?? t('keychain')) ?>">
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<div class="wear-meter" title="<?= h(t('wear_value') . ': ' . ($usesInventorySkin ? '?' : $initialWearValue)) ?>">
								<span class="visually-hidden"><?= h(t('wear_value') . ': ' . ($usesInventorySkin ? '?' : $initialWearValue)) ?></span>
								<div class="wear-value"><?= h(t('wear_value')) ?>: <?= $usesInventorySkin ? '?' : h($initialWearValue) ?></div>
								<?php if (!$usesInventorySkin) : ?>
									<div class="wear-pointer-icon" style="left: <?= h(max(0, min(100, (float)$initialWearValue * 100))) ?>%"></div>
								<?php endif; ?>
								<div class="progress">
									<div class="progress-bar progress-bar-fn" style="width: 7%" title="<?= h(t('wear_factory_new')) ?>"></div>
									<div class="progress-bar progress-bar-mw" style="width: 8%" title="<?= h(t('wear_minimal_wear')) ?>"></div>
									<div class="progress-bar progress-bar-ft" style="width: 23%" title="<?= h(t('wear_field_tested')) ?>"></div>
									<div class="progress-bar progress-bar-ww" style="width: 7%" title="<?= h(t('wear_well_worn')) ?>"></div>
									<div class="progress-bar progress-bar-bs" style="width: 55%" title="<?= h(t('wear_battle_scarred')) ?>"></div>
								</div>
							</div>
							<div class="settings-row">
								<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#<?= h($skinPickerId) ?>">
									<?= h(t('choose_skin')) ?>
								</button>
								<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#<?= h($modalId) ?>" <?= $currentSkinCanEdit ? '' : 'disabled' ?>>
									<?= h(t('edit')) ?>
								</button>
								<?= inspectButton(
									$defindex,
									inspectHexFromValues($defindex, $currentPaintId, $initialWearValue, $initialSeedValue, $initialStatTrakValue, $initialStatTrakCountValue, $initialNameTagValue, $initialStickerValues, $initialKeychainValue),
									($default['weapon_name'] ?? '') . ' — ' . ($currentSkin['paint_name'] ?? '')
								) ?>
							</div>

							<div class="modal fade skin-picker-modal" id="<?= h($skinPickerId) ?>" tabindex="-1" aria-hidden="true">
								<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title"><?= h(sprintf(t('choose_skin_for'), fusionTargetName($default, $default['weapon_name'] ?? ''))) ?></h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
										</div>
										<div class="modal-body picker-modal-body">
											<div class="picker-search-bar">
												<input type="search" class="form-control picker-search" placeholder="<?= h(t('search_skin')) ?>" autocomplete="off" data-picker-search>
											</div>
											<div class="skin-picker-grid picker-results-scroll">
												<?php $skinPosition = 0; ?>
												<?php foreach ($skins[$defindex] as $paintKey => $paint) : ?>
													<?php $paintImage = (string)($paint['image_url'] ?? ''); ?>
									<button type="submit" name="skin_forma" value="<?= (int)$defindex ?>-<?= (int)$paintKey ?>" class="skin-result <?= $currentPaintId === (int)$paintKey ? 'active' : '' ?>" data-picker-result data-search="<?= h(trim(($paint['paint_name'] ?? '') . ' ' . ($skinAliases[$defindex][(int)$paintKey] ?? ''))) ?>">
														<?php if ($weaponPlaceholder !== '') : ?>
															<img src="<?= h($weaponPlaceholder) ?>" data-picker-remote-src="<?= h($paintImage) ?>" alt="">
														<?php elseif ($paintImage !== '') : ?>
															<img src="<?= h($paintImage) ?>" alt="">
												<?php else : ?>
													<div class="empty-image"><?= h($paint['paint_name']) ?></div>
												<?php endif; ?>
												<?= paintKitFinishBadgeHtml($paintKey) ?>
											<span><?= h($paint['paint_name']) ?></span>
												</button>
												<?php if ($skinPosition === 0 && skinFusionEnabled()) : ?>
											<button type="button" class="skin-result fusion-result" data-picker-result data-search="<?= h(t('fusion_finish_entry')) ?>" data-fusion-open data-fusion-defindex="<?= (int)$defindex ?>" data-fusion-weapon="<?= h($default['weapon_name'] ?? '') ?>" data-fusion-target-name="<?= h(fusionTargetName($default, $default['weapon_name'] ?? '')) ?>" data-fusion-official-paints="<?= h(implode(',', array_map('intval', array_keys($skins[$defindex] ?? [])))) ?>">
														<?php if ($weaponPlaceholder !== '') : ?>
															<img src="<?= h($weaponPlaceholder) ?>" alt="">
														<?php else : ?>
													<div class="empty-image"><?= h(t('fusion_finish_entry')) ?></div>
														<?php endif; ?>
														<span class="fusion-badge fusion-picker-badge"><?= h(t('skin_fusion')) ?></span>
												<span><?= h(t('fusion_finish_entry')) ?></span>
													</button>
												<?php endif; ?>
												<?php $skinPosition++; ?>
											<?php endforeach; ?>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="modal fade skin-edit-modal" id="<?= h($modalId) ?>" tabindex="-1" aria-hidden="true">
								<div class="modal-dialog modal-dialog-centered">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title"><?= h($currentSkin['paint_name']) ?> <?= h(t('settings')) ?></h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
										</div>
										<div class="modal-body">
										<div class="row g-3">
											<div class="col-12 skin-param-grid">
												<div class="skin-param-row" data-skin-param-row>
													<label for="wear<?= (int)$defindex ?>"><?= h(t('wear_value')) ?></label>
													<div class="skin-param-control">
														<input type="range" min="0" max="1" step="0.01" value="<?= h($initialWearValue) ?>" data-skin-param-range>
												<input type="number" min="0" max="1" step="0.01" value="<?= h(skinWearDisplayValue($initialWearValue)) ?>" class="form-control" id="wear<?= (int)$defindex ?>" name="wear" data-skin-param-number data-max-decimals="8">
													</div>
												</div>
												<div class="skin-param-row" data-skin-param-row>
													<label for="seed<?= (int)$defindex ?>"><?= h(t('pattern')) ?></label>
													<div class="skin-param-control">
														<input type="range" min="0" max="1000" step="1" value="<?= h($initialSeedValue) ?>" data-skin-param-range>
														<input id="seed<?= (int)$defindex ?>" type="number" min="0" max="1000" step="1" value="<?= h($initialSeedValue) ?>" class="form-control" name="seed" data-skin-param-number>
													</div>
												</div>
											</div>
												<div class="col-12 weapon-option-grid">
												<div class="nametag-row weapon-option-card">
													<input type="hidden" name="nametag_present" value="1">
													<label class="check-line">
														<input type="checkbox" name="nametag_enabled" value="1" data-nametag-toggle <?= $initialNameTagEnabled ? 'checked' : '' ?>>
														<span class="nametag-label"><?= h(t('name_tag')) ?></span>
													</label>
											<input type="text" name="weapon_nametag" value="<?= h($initialNameTagValue ?? '') ?>" maxlength="20" autocomplete="off" autocapitalize="off" spellcheck="false" class="form-control nametag-input<?= $initialNameTagEnabled ? '' : ' is-inactive' ?>" data-nametag-input <?= $initialNameTagEnabled ? '' : 'disabled' ?>>
												</div>
												<div class="stattrak-row weapon-option-card">
	<label class="check-line">
		<input type="checkbox" name="stattrak" value="1" data-stattrak-toggle <?= $initialStatTrakValue ? 'checked' : '' ?>>
		<span class="stattrak-label">StatTrak™</span>
	</label>
	<input type="number" name="weapon_stattrak_count" value="<?= h($initialStatTrakCountValue) ?>" min="0" max="999999" step="1" class="form-control stattrak-input<?= $initialStatTrakValue ? '' : ' is-inactive' ?>" data-stattrak-input <?= $initialStatTrakValue ? '' : 'disabled' ?> <?= stattrakCountEditable() ? "" : "readonly" ?> title="<?= h(stattrakCountEditable() ? "" : t("stattrak_count_locked")) ?>">
	<button type="submit" name="stattrak_reset" value="1" class="btn btn-sm btn-outline-light stattrak-reset" onclick="return confirm(<?= h(json_encode(t("stattrak_reset_confirm"), JSON_UNESCAPED_UNICODE)) ?>);" title="<?= h(t("stattrak_reset_title")) ?>"><?= h(t("stattrak_reset")) ?></button>
												</div>
</div>
								<?php if ($allowsCustomization) : ?>
								<div class="col-12 cosmetic-editor">
												<section class="customization-panel sticker-section">
													<input type="hidden" name="sticker_present" value="1">
													<div class="sticker-section-heading">
														<div class="sticker-section-title"><?= h(t('stickers')) ?></div>
														<div class="sticker-tool-buttons">
															<span class="sticker-tool-button-wrap" title="<?= h(t('apply_sticker_to_all')) ?>">
																<button type="button" class="btn btn-sm btn-outline-light sticker-tool-button" data-sticker-fill-all title="<?= h(t('apply_sticker_to_all')) ?>" aria-label="<?= h(t('apply_sticker_to_all')) ?>" disabled>
																	↻
																</button>
															</span>
															<span class="sticker-tool-button-wrap" title="<?= h(t('clear_all_stickers')) ?>">
																<button type="button" class="btn btn-sm btn-outline-light sticker-tool-button" data-sticker-clear-all title="<?= h(t('clear_all_stickers')) ?>" aria-label="<?= h(t('clear_all_stickers')) ?>" disabled>
																	×
																</button>
															</span>
														</div>
													</div>
													<div class="sticker-grid">
														<?php for ($slotIndex = 0; $slotIndex < $stickerSlotTotal; $slotIndex++) : ?>
													<?php
													$currentStickerValue = $initialStickerValues[$slotIndex] ?? defaultStickerValue();
													$currentStickerId = $initialStickerIds[$slotIndex] ?? 0;
									$currentSticker = $stickers[$currentStickerId] ?? unknownItemData($currentStickerId);
													?>
													<div class="sticker-slot" data-empty-label="<?= h(t('sticker_slot') . ' ' . ($slotIndex + 1)) ?>" data-slot-number="<?= $slotIndex + 1 ?>" data-sticker-slot-index="<?= $slotIndex ?>" data-weapon-defindex="<?= (int)$defindex ?>" data-saved-sticker-id="<?= (int)$currentStickerId ?>">
														<input type="hidden" name="sticker_<?= $slotIndex ?>" value="<?= (int)$currentStickerId ?>" data-sticker-input>
														<input type="hidden" name="sticker_value_<?= $slotIndex ?>" value="<?= h($currentStickerValue) ?>" data-sticker-value>
																<div class="sticker-slot-preview">
																	<span class="sticker-slot-index" aria-hidden="true"><?= $slotIndex + 1 ?></span>
																	<button type="button" class="sticker-slot-button" data-sticker-open aria-label="<?= h(t('choose_sticker')) ?>">
																<span class="sticker-plus sticker-empty-icon" <?= $currentStickerId > 0 ? 'hidden' : '' ?>>+</span>
																<img src="img/skins/sticker.png" data-remote-src="<?= h($currentSticker['image'] ?? '') ?>" alt="" data-sticker-preview <?= $currentStickerId > 0 ? '' : 'hidden' ?> >
															</button>
															<button type="button" class="sticker-slot-settings" data-sticker-settings title="<?= h(t('sticker_settings')) ?>" aria-label="<?= h(t('sticker_settings')) ?>" <?= $currentStickerId > 0 ? '' : 'hidden disabled' ?>>⚙</button>
														</div>
														<div class="sticker-slot-name" data-sticker-name><span data-sticker-name-text><?= h($currentStickerId > 0 ? ($currentSticker['name'] ?? '') : t('sticker_slot') . ' ' . ($slotIndex + 1)) ?></span></div>
													</div>
														<?php endfor; ?>
													</div>
												</section>
												<section class="customization-panel keychain-section">
													<input type="hidden" name="keychain_present" value="1">
													<div class="keychain-section-heading">
														<div class="keychain-section-title"><?= h(t('keychain')) ?></div>
													</div>
											<div class="keychain-inline-editor">
												<div class="keychain-grid">
												<div class="keychain-slot" data-empty-label="<?= h(t('no_keychain')) ?>" data-keychain-slot data-weapon-defindex="<?= (int)$defindex ?>" data-saved-keychain-id="<?= (int)$initialKeychainId ?>">
													<input type="hidden" name="keychain_id" value="<?= (int)$initialKeychainId ?>" data-keychain-input>
													<input type="hidden" name="keychain_value" value="<?= h($initialKeychainValue) ?>" data-keychain-value>
															<div class="keychain-slot-preview">
																<button type="button" class="keychain-slot-button" data-keychain-open aria-label="<?= h(t('choose_keychain')) ?>">
																	<span class="keychain-plus keychain-empty-icon" <?= $initialKeychainId > 0 ? 'hidden' : '' ?>>+</span>
															<img src="img/skins/keychain.png" data-remote-src="<?= h($initialKeychain['image'] ?? '') ?>" alt="" data-keychain-preview <?= $initialKeychainId > 0 ? '' : 'hidden' ?> >
														</button>
													</div>
													<div class="keychain-slot-name" data-keychain-name><span data-keychain-name-text><?= h($initialKeychainId > 0 ? ($initialKeychain['name'] ?? '') : t('no_keychain')) ?></span></div>
												</div>
												</div>
											<div class="keychain-inline-controls" data-keychain-inline-controls>
												<div class="keychain-param-row" data-keychain-param-row="template">
													<label for="keychainTemplate<?= (int)$defindex ?>"><?= h(t('keychain_template')) ?></label>
													<div class="keychain-param-control">
														<input type="range" min="1" max="99999" step="1" value="<?= h($initialKeychainParts['template']) ?>" data-keychain-inline-range="template" <?= $initialKeychainId > 0 ? '' : 'disabled' ?>>
														<input id="keychainTemplate<?= (int)$defindex ?>" type="number" name="keychain_template" min="1" max="99999" step="1" value="<?= h($initialKeychainParts['template']) ?>" class="form-control" data-keychain-inline-param="template" <?= $initialKeychainId > 0 ? '' : 'disabled' ?>>
													</div>
												</div>
												<div class="keychain-param-row" data-keychain-param-row="x">
													<label for="keychainX<?= (int)$defindex ?>"><?= h(t('keychain_x')) ?></label>
													<div class="keychain-param-control">
														<input type="range" min="-<?= KEYCHAIN_OFFSET_LIMIT ?>" max="<?= KEYCHAIN_OFFSET_LIMIT ?>" step="0.01" value="<?= h(stickerFloatValue($initialKeychainParts['x'])) ?>" data-keychain-inline-range="x" <?= $initialKeychainId > 0 ? '' : 'disabled' ?>>
														<input id="keychainX<?= (int)$defindex ?>" type="number" name="keychain_x" min="-<?= KEYCHAIN_OFFSET_LIMIT ?>" max="<?= KEYCHAIN_OFFSET_LIMIT ?>" step="0.01" value="<?= h(stickerFloatValue($initialKeychainParts['x'])) ?>" class="form-control" data-keychain-inline-param="x" <?= $initialKeychainId > 0 ? '' : 'disabled' ?>>
													</div>
												</div>
												<div class="keychain-param-row" data-keychain-param-row="y">
													<label for="keychainY<?= (int)$defindex ?>"><?= h(t('keychain_y')) ?></label>
													<div class="keychain-param-control">
														<input type="range" min="-<?= KEYCHAIN_OFFSET_LIMIT ?>" max="<?= KEYCHAIN_OFFSET_LIMIT ?>" step="0.01" value="<?= h(stickerFloatValue($initialKeychainParts['y'])) ?>" data-keychain-inline-range="y" <?= $initialKeychainId > 0 ? '' : 'disabled' ?>>
														<input id="keychainY<?= (int)$defindex ?>" type="number" name="keychain_y" min="-<?= KEYCHAIN_OFFSET_LIMIT ?>" max="<?= KEYCHAIN_OFFSET_LIMIT ?>" step="0.01" value="<?= h(stickerFloatValue($initialKeychainParts['y'])) ?>" class="form-control" data-keychain-inline-param="y" <?= $initialKeychainId > 0 ? '' : 'disabled' ?>>
													</div>
												</div>
											</div>
											</div>
											</section>
												</div>
								<?php endif; ?>
											</div>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
											<button type="submit" class="btn btn-primary"><?= h(t('save')) ?></button>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				<?php endforeach; ?>
