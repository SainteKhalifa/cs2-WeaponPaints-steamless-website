				<?php if (in_array($team, [2, 3], true)) : ?>
					<?php
					$agentOptions = array_values(array_filter($agents, static fn($agent) => (int)($agent['team'] ?? 0) === $team));
					$currentAgent = $agentOptions[0] ?? ['model' => '', 'agent_name' => t('default_agent'), 'image' => ''];
					foreach ($agentOptions as $agent) {
						if (($selectedAgent ?? '') === $agent['model']) {
							$currentAgent = $agent;
							break;
						}
					}
					if (($selectedAgent ?? '') !== '' && ($currentAgent['model'] ?? '') !== $selectedAgent) {
						$currentAgent = [
							'model' => $selectedAgent,
							'agent_name' => sprintf(t('unknown_agent'), $selectedAgent),
							'image' => '',
						];
					}
					?>
					<div class="skin-card featured">
						<div class="card-title-wrap">
							<span><?= h($team === 2 ? t('t_agent') : t('ct_agent')) ?></span>
							<h2><?= h($currentAgent['agent_name']) ?></h2>
						</div>
						<?php if (!empty($currentAgent['image'])) : ?>
							<img src="img/skins/agent.png" data-remote-src="<?= h($currentAgent['image']) ?>" class="skin-image" alt="">
						<?php else : ?>
							<img src="img/skins/agent.png" class="skin-image" alt="">
						<?php endif; ?>
						<div class="settings-row">
							<button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#agentModal">
								<?= h(t('select')) ?>
							</button>
						</div>

						<form method="post" class="modal-form">
							<?= csrfInput() ?>
							<input type="hidden" name="action" value="save_agent">
							<input type="hidden" name="id" value="<?= h($currentPreset['steamid']) ?>">
							<input type="hidden" name="team" value="<?= $team ?>">
							<div class="modal fade agent-picker-modal" id="agentModal" tabindex="-1" aria-hidden="true">
								<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title"><?= h(t('choose_agent')) ?></h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('close')) ?>"></button>
										</div>
										<div class="modal-body">
											<div class="agent-picker-grid">
												<?php foreach ($agentOptions as $agent) : ?>
													<?php $agentImage = (string)($agent['image'] ?? ''); ?>
													<button type="submit" name="agent_model" value="<?= h($agent['model']) ?>" class="agent-result <?= ($currentAgent['model'] ?? '') === $agent['model'] ? 'active' : '' ?>">
														<?php if ($agentImage !== '') : ?>
													<img src="img/skins/agent.png" data-picker-remote-src="<?= h($agentImage) ?>" alt="">
														<?php else : ?>
															<img src="img/skins/agent.png" alt="">
														<?php endif; ?>
														<span><?= h($agent['agent_name']) ?></span>
													</button>
												<?php endforeach; ?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				<?php endif; ?>
