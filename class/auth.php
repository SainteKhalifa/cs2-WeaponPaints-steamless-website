<?php

function authRateLimitConfig($scope)
{
	return [
		'attempts' => max(1, (int)(defined('AUTH_RATE_LIMIT_ATTEMPTS') ? AUTH_RATE_LIMIT_ATTEMPTS : 5)),
		'window' => max(1, (int)(defined('AUTH_RATE_LIMIT_WINDOW_SECONDS') ? AUTH_RATE_LIMIT_WINDOW_SECONDS : 1800)),
		'lock' => max(1, (int)(defined('AUTH_RATE_LIMIT_LOCK_SECONDS') ? AUTH_RATE_LIMIT_LOCK_SECONDS : 60)),
	];
}

function authRateLimitSessionFallback($scope, $subject, $operation, $config, $seedState = null, $operationApplied = false)
{
	$key = hash('sha256', (string)$scope . '|' . (string)$subject);
	$now = time();
	$policy = hash('sha256', implode('|', [$config['attempts'], $config['window'], $config['lock']]));
	$sessionState = $_SESSION['cs2_auth_rate_limit_fallback'][$key] ?? null;
	$usingSeedState = !is_array($sessionState) && is_array($seedState);
	$state = is_array($sessionState) ? $sessionState : $seedState;
	if (!is_array($state) || !hash_equals($policy, (string)($state['policy'] ?? ''))) {
		$state = ['attempts' => [], 'blocked_until' => 0, 'policy' => $policy];
	}
	$state['attempts'] = array_values(array_filter((array)($state['attempts'] ?? []), static function ($timestamp) use ($now, $config) {
		return is_numeric($timestamp) && (int)$timestamp > $now - $config['window'];
	}));
	$state['blocked_until'] = max(0, (int)($state['blocked_until'] ?? 0));
	if ($operationApplied && $usingSeedState) {
		// The file-backed state already includes this operation; only persist the fallback copy.
	} elseif ($operation === 'clear') {
		$state = ['attempts' => [], 'blocked_until' => 0, 'policy' => $policy];
	} elseif ($state['blocked_until'] <= $now) {
		$state['blocked_until'] = 0;
		if ($operation === 'fail') {
			$state['attempts'][] = $now;
			if (count($state['attempts']) >= $config['attempts']) {
				$state['blocked_until'] = $now + $config['lock'];
				$state['attempts'] = [];
			}
		}
	}
	$_SESSION['cs2_auth_rate_limit_fallback'][$key] = $state;
	return max(0, $state['blocked_until'] - $now);
}

function authRateLimit($scope, $subject = '', $operation = 'check')
{
	$config = authRateLimitConfig($scope);
	$clientAddress = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
	$key = hash('sha256', __DIR__ . '|' . $clientAddress . '|' . $scope . '|' . (string)$subject);
	$directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cs2_weaponpaints_rate_limits';
	if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
		error_log("Unable to create authentication rate-limit directory: {$directory}; using session fallback.");
		return authRateLimitSessionFallback($scope, $subject, $operation, $config);
	}

	$handle = @fopen($directory . DIRECTORY_SEPARATOR . $key . '.json', 'c+');
	if ($handle === false || !flock($handle, LOCK_EX)) {
		if (is_resource($handle)) {
			fclose($handle);
		}
		error_log("Unable to lock authentication rate-limit state for scope {$scope}; using session fallback.");
		return authRateLimitSessionFallback($scope, $subject, $operation, $config);
	}

	$now = time();
	$policy = hash('sha256', implode('|', [$config['attempts'], $config['window'], $config['lock']]));
	rewind($handle);
	$state = json_decode((string)stream_get_contents($handle), true);
	if (!is_array($state) || !hash_equals($policy, (string)($state['policy'] ?? ''))) {
		$state = ['attempts' => [], 'blocked_until' => 0, 'policy' => $policy];
	}
	$state['attempts'] = array_values(array_filter((array)($state['attempts'] ?? []), static function ($timestamp) use ($now, $config) {
		return is_numeric($timestamp) && (int)$timestamp > $now - $config['window'];
	}));
	$state['blocked_until'] = max(0, (int)($state['blocked_until'] ?? 0));

	if ($operation === 'clear') {
		$state = ['attempts' => [], 'blocked_until' => 0, 'policy' => $policy];
	} elseif ($state['blocked_until'] <= $now) {
		$state['blocked_until'] = 0;
		if ($operation === 'fail') {
			$state['attempts'][] = $now;
			if (count($state['attempts']) >= $config['attempts']) {
				$state['blocked_until'] = $now + $config['lock'];
				$state['attempts'] = [];
			}
		}
	}

	$encodedState = json_encode($state);
	$writeSucceeded = $encodedState !== false
		&& rewind($handle)
		&& ftruncate($handle, 0);
	if ($writeSucceeded) {
		$bytesWritten = fwrite($handle, $encodedState);
		$writeSucceeded = $bytesWritten === strlen($encodedState) && fflush($handle);
	}
	flock($handle, LOCK_UN);
	fclose($handle);
	if (!$writeSucceeded) {
		error_log("Unable to persist authentication rate-limit state for scope {$scope}; using session fallback.");
		return authRateLimitSessionFallback($scope, $subject, $operation, $config, $state, true);
	}
	return max(0, $state['blocked_until'] - $now);
}

