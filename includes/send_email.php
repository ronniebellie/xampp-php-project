<?php
/**
 * Send email via SendGrid HTTP API (port 443) or SMTP.
 * Requires includes/email_config.php with smtp_pass (API key), from_email, from_name.
 * Uses HTTP API by default (avoids SMTP port 587 which may be blocked by firewalls).
 *
 * @param string $to      Recipient email
 * @param string $subject Subject line
 * @param string $body    Plain text body
 * @return bool True on success, false on failure
 */
function send_email_smtp($to, $subject, $body) {
    $configPath = __DIR__ . '/email_config.php';
    if (!file_exists($configPath)) {
        error_log('send_email: email_config.php not found');
        return false;
    }
    $config = require $configPath;
    if (empty($config['smtp_pass'])) {
        error_log('send_email: email_config.php incomplete (smtp_pass required)');
        return false;
    }

    $apiKey = $config['smtp_pass'];
    $fromEmail = $config['from_email'] ?? 'noreply@calcforadvisors.com';
    $fromName = $config['from_name'] ?? 'calcforadvisors.com';

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
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $result = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code < 200 || $code >= 300) {
            error_log('send_email: SendGrid API returned ' . $code . ' - ' . substr($result, 0, 200));
            return false;
        }
        if ($result === false && $err) {
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
        error_log('send_email: SendGrid API failed - code=' . $code);
        return false;
    }
    return true;
}
