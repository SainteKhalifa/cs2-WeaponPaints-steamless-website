				<?php
				$gloveTypes = gloveTypeOptions($gloves);
				$actualGloveDefindex = max(0, (int)($selectedGlove['weapon_defindex'] ?? 0));
				$gloveTypeIsKnown = array_key_exists($actualGloveDefindex, $gloves);
				$actualGlove = $gloveTypes[$actualGloveDefindex] ?? [
					'weapon_name' => '',
					'paint_name' => $actualGloveDefindex > 0 ? sprintf(t('unknown_item'), $actualGloveDefindex) : (UtilsClass::currentLanguage() === 'en' ? 'Use inventory gloves' : '使用库存手套'),
					'image_url' => '',
				];
				$gloveSkinOptions = $actualGloveDefindex > 0 ? ($gloves[$actualGloveDefindex] ?? []) : [];
				$selectedGloveSkin = $actualGloveDefindex > 0 ? ($selectedSkins[$actualGloveDefindex] ?? null) : null;
				$currentGlovePaintId = $selectedGloveSkin ? (int)$selectedGloveSkin['weapon_paint_id'] : 0;
				$currentGloveSkin = $actualGloveDefindex > 0 && isset($gloveSkinOptions[$currentGlovePaintId])
					? $gloveSkinOptions[$currentGlovePaintId]
					: ($selectedGloveSkin && $currentGlovePaintId > 0
						? unknownSkinData($actualGlove, $currentGlovePaintId, $actualGlove['paint_name'] ?? '')
						: $actualGlove);
				$currentGloveWear = $selectedGloveSkin['weapon_wear'] ?? 0.0;
				$currentGloveSeed = $selectedGloveSkin['weapon_seed'] ?? 0;
				$currentGloveCanEdit = $actualGloveDefindex > 0 && $selectedGloveSkin !== null && $currentGlovePaintId > 0;
				?>
				<div class="skin-card featured loadout-card">
					<div class="card-title-wrap">
						<span><?= h(t('gloves')) ?></span>
						<h2><?= h($currentGloveSkin['paint_name']) ?></h2>
					</div>
					<div class="skin-visual">
						<?php $glovePlaceholder = glovePlaceholderImage($actualGloveDefindex); ?>
						<?php if ($glovePlaceholder !== '') : ?>
							<img src="<?= h($glovePlaceholder) ?>" data-remote-src="<?= h($currentGloveSkin['image_url'] ?? '') ?>" class="skin-image" alt="">
						<?php elseif (!empty($currentGloveSkin['image_url'])) : ?>
							<img src="<?= h($currentGloveSkin['image_url']) ?>" class="skin-image" alt="">
						<?php else : ?>
							<div class="empty-image"><?= h(t('default_gloves')) ?></div>
						<?php endif; ?>
						<span class="pattern-badge"><?= h(t('pattern')) ?> <?= h($currentGloveSeed) ?></span>
					</div>
					<div class="wear-meter" title="<?= h(t('wear_value') . ' ' . $currentGloveWear) ?>">
						<span class="visually-hidden"><?= h(t('wear_value') . ' ' . $currentGloveWear) ?></span>
						<div class="wear-value"><?= h(t('wear_value')) ?>: <?= h($currentGloveWear) ?></div>
						<div class="wear-pointer-icon" style="left: <?= h(max(0, min(100, (float)$currentGloveWear * 100))) ?>%"></div>
						<div class="progress">
							<div class="progress-bar progress-bar-fn" style="width: 7%" title="<?= h(t('wear_factory_new')) ?>"></div>
							<div class="progress-bar progress-bar-mw" style="width: 8%" title="<?= h(t('wear_minimal_wear')) ?>"></div>
							<div class="progress-bar progress-bar-ft" style="width: 23%" title="<?= h(t('wear_field_tested')) ?>"></div>
							<div class="progress-bar progress-bar-ww" style="width: 7%" title="<?= h(t('wear_well_worn')) ?>"></div>
							<div class="progress-bar progress-bar-bs" style="width: 55%" title="<?= h(t('wear_battle_scarred')) ?>"></div>
						</div>
					</div>
					<div class="settings-row">
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#gloveTypeModal">
							<?= h(t('choose_type')) ?>
						</button>
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#gloveSkinModal" <?= ($actualGloveDefindex === 0 || !$gloveTypeIsKnown) ? 'disabled' : '' ?>>
							<?= h(t('choose_skin')) ?>
						</button>
						<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#gloveModal" <?= $currentGloveCanEdit ? '' : 'disabled' ?>>
							<?= h(t('edit')) ?>
						</button>
					</div>

					<form method="post" class="modal-form">
						<?= csrfInput() ?>
						<input type="hidden" name="action" value="save_skin">
						<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
						<input type="hidden" name="team" value="<?= $team ?>">
						<div class="modal fade skin-picker-modal" id="gloveTypeModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(t('choose_type_title')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<div class="skin-picker-grid">
											<?php foreach ($gloveTypes as $gloveDefindex => $gloveType) : ?>
												<?php
												$gloveTypePlaceholder = glovePlaceholderImage((int)$gloveDefindex);
												$gloveTypeImage = (string)($gloveType['image_url'] ?? '');
												?>
												<button type="submit" name="forma" value="glove-<?= (int)$gloveDefindex ?>" class="skin-result <?= $actualGloveDefindex === (int)$gloveDefindex ? 'active' : '' ?>">
													<?php if ($gloveTypePlaceholder !== '') : ?>
														<img src="<?= h($gloveTypePlaceholder) ?>" alt="">
													<?php elseif ($gloveTypeImage !== '') : ?>
														<img src="<?= h($gloveTypeImage) ?>" alt="">
													<?php else : ?>
														<div class="empty-image"><?= h($gloveType['paint_name']) ?></div>
													<?php endif; ?>
													<span><?= h($gloveType['paint_name']) ?></span>
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
						<div class="modal fade skin-picker-modal" id="gloveSkinModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h(sprintf(t('choose_skin_for'), fusionTargetName($actualGlove, t('gloves')))) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body picker-modal-body">
										<?php if ($actualGloveDefindex === 0) : ?>
											<p class="hint"><?= h(t('choose_glove_hint')) ?></p>
										<?php else : ?>
											<div class="picker-search-bar">
												<input type="search" class="form-control picker-search" placeholder="<?= h(t('search_skin')) ?>" autocomplete="off" data-picker-search>
											</div>
											<div class="skin-picker-grid picker-results-scroll">
												<?php foreach ($gloveSkinOptions as $paintKey => $paint) : ?>
													<?php $gloveSkinImage = (string)($paint['image_url'] ?? ''); ?>
									<button type="submit" name="skin_forma" value="gloveskin-<?= (int)$actualGloveDefindex ?>-<?= (int)$paintKey ?>" class="skin-result <?= $currentGlovePaintId === (int)$paintKey ? 'active' : '' ?>" data-picker-result data-search="<?= h(trim(($paint['paint_name'] ?? '') . ' ' . ($gloveAliases[$actualGloveDefindex][(int)$paintKey] ?? ''))) ?>">
														<?php if ($glovePlaceholder !== '') : ?>
															<img src="<?= h($glovePlaceholder) ?>" data-picker-remote-src="<?= h($gloveSkinImage) ?>" alt="">
														<?php elseif ($gloveSkinImage !== '') : ?>
															<img src="<?= h($gloveSkinImage) ?>" alt="">
												<?php else : ?>
													<div class="empty-image"><?= h($paint['paint_name']) ?></div>
												<?php endif; ?>
												<?= paintKitFinishBadgeHtml($paintKey) ?>
												<span><?= h($paint['paint_name']) ?></span>
													</button>
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
						<input type="hidden" name="forma" value="gloveskin-<?= (int)$actualGloveDefindex ?>-<?= (int)$currentGlovePaintId ?>">
						<div class="modal fade skin-edit-modal" id="gloveModal" tabindex="-1" aria-hidden="true">
							<div class="modal-dialog modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title"><?= h($currentGloveSkin['paint_name']) ?> <?= h(t('settings')) ?></h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
									</div>
									<div class="modal-body">
										<?php if ($actualGloveDefindex === 0) : ?>
											<p class="hint"><?= h(t('choose_glove_hint')) ?></p>
										<?php else : ?>
											<div class="row g-3">
												<div class="col-12 skin-param-grid">
													<div class="skin-param-row" data-skin-param-row>
														<label for="gloveWear"><?= h(t('wear_value')) ?></label>
														<div class="skin-param-control">
															<input type="range" min="0" max="1" step="0.01" value="<?= h($currentGloveWear) ?>" data-skin-param-range>
													<input id="gloveWear" type="number" min="0" max="1" step="0.01" value="<?= h(skinWearDisplayValue($currentGloveWear)) ?>" class="form-control" name="wear" data-skin-param-number data-max-decimals="8">
														</div>
													</div>
													<div class="skin-param-row" data-skin-param-row>
														<label for="gloveSeed"><?= h(t('pattern')) ?></label>
														<div class="skin-param-control">
															<input type="range" min="0" max="1000" step="1" value="<?= h($currentGloveSeed) ?>" data-skin-param-range>
															<input id="gloveSeed" type="number" min="0" max="1000" step="1" value="<?= h($currentGloveSeed) ?>" class="form-control" name="seed" data-skin-param-number>
														</div>
													</div>
												</div>
											</div>
										<?php endif; ?>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('cancel')) ?></button>
										<?php if ($actualGloveDefindex > 0 && $currentGlovePaintId > 0) : ?><button type="submit" class="btn btn-primary"><?= h(t('save')) ?></button><?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
