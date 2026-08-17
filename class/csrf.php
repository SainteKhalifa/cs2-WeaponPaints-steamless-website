<?php

function csrfToken()
{
	$token = $_SESSION['csrf_token'] ?? '';
	if (!is_string($token) || strlen($token) !== 64) {
		$token = bin2hex(random_bytes(32));
		$_SESSION['csrf_token'] = $token;
	}
	return $token;
}

function csrfInput()
{
	return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function requestCsrfToken()
{
	return (string)($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
}

function isAjaxRequest()
{
	return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'fetch'
		|| (string)($_POST['ajax'] ?? '') === '1';
}

function rejectInvalidCsrf()
{
	http_response_code(403);
	if (isAjaxRequest()) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['ok' => false, 'message' => t('csrf_invalid')], JSON_UNESCAPED_UNICODE);
	} else {
		header('Content-Type: text/plain; charset=utf-8');
		echo t('csrf_invalid');
	}
	exit;
}

function verifyCsrfRequest()
{
	$submitted = requestCsrfToken();
	return $submitted !== '' && hash_equals(csrfToken(), $submitted);
}
