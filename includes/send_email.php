<?php
/**
 * Send email via SendGrid HTTP API (port 443).
 * Requires includes/email_config.php with smtp_pass (API key), from_email, from_name.
 *
 * @param string $to      Recipient email
 * @param string $subject Subject line
 * @param string $body    Plain text body
 * @return bool True on success, false on failure
 */
function send_email_smtp($to, $subject, $body) {
    $GLOBALS['rb_send_email_last_error'] = null;

    // Test / local override — never used in production unless explicitly set.
    if (isset($GLOBALS['rb_send_email_handler']) && is_callable($GLOBALS['rb_send_email_handler'])) {
        try {
            return (bool) $GLOBALS['rb_send_email_handler']($to, $subject, $body);
        } catch (Throwable $e) {
            $GLOBALS['rb_send_email_last_error'] = 'handler_exception';
            return false;
        }
    }

    $configPath = __DIR__ . '/email_config.php';
    if (!file_exists($configPath)) {
        $GLOBALS['rb_send_email_last_error'] = 'config_missing';
        error_log('send_email: email_config.php not found');
        return false;
    }
    $config = require $configPath;
    if (empty($config['smtp_pass'])) {
        $GLOBALS['rb_send_email_last_error'] = 'config_incomplete';
        error_log('send_email: email_config.php incomplete (smtp_pass required)');
        return false;
    }

    $apiKey = $config['smtp_pass'];
    $fromEmail = $config['from_email'] ?? 'noreply@ronbelisle.com';
    $fromName = $config['from_name'] ?? 'Ron Belisle';

    $payload = [
        'personalizations' => [['to' => [['email' => $to]]]],
        'from' => ['email' => $fromEmail, 'name' => $fromName],
        'subject' => $subject,
        'content' => [['type' => 'text/plain', 'value' => $body]],
    ];

    $json = json_encode($payload);
    $url = 'https://api.sendgrid.com/v3/mail/send';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: ' . 'application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $result = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code < 200 || $code >= 300) {
            $snippet = is_string($result) ? substr($result, 0, 300) : '';
            error_log('send_email: SendGrid API returned ' . $code . ' - ' . $snippet);
            if ($code === 401 && (stripos($snippet, 'Maximum credits exceeded') !== false || stripos($snippet, 'credits') !== false)) {
                $GLOBALS['rb_send_email_last_error'] = 'credits_exceeded';
            } elseif ($code === 401 || $code === 403) {
                $GLOBALS['rb_send_email_last_error'] = 'auth_failed';
            } else {
                $GLOBALS['rb_send_email_last_error'] = 'provider_http_' . $code;
            }
            return false;
        }
        if ($result === false && $err) {
            $GLOBALS['rb_send_email_last_error'] = 'curl_failed';
            error_log('send_email: cURL failed - ' . $err);
            return false;
        }
        return true;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer $apiKey\r\nContent-Type: application/json\r\n",
            'content' => $json,
            'timeout' => 15,
        ],
    ]);
    $result = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m)) {
        $code = (int) $m[1];
    }
    if ($result === false || $code < 200 || $code >= 300) {
        $GLOBALS['rb_send_email_last_error'] = 'provider_http_' . $code;
        error_log('send_email: SendGrid API failed - code=' . $code);
        return false;
    }
    return true;
}

function rb_send_email_last_error(): ?string
{
    $err = $GLOBALS['rb_send_email_last_error'] ?? null;
    return is_string($err) && $err !== '' ? $err : null;
}
