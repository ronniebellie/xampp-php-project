<?php
/**
 * Send email via SMTP (PHPMailer).
 * Requires includes/email_config.php with smtp_host, smtp_port, smtp_user, smtp_pass, from_email, from_name.
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
    if (empty($config['smtp_host']) || empty($config['smtp_pass'])) {
        error_log('send_email: email_config.php incomplete');
        return false;
    }

    $vendor = file_exists(__DIR__ . '/../vendor/autoload.php') ? __DIR__ . '/../vendor' : __DIR__ . '/../html/vendor';
    require_once $vendor . '/autoload.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'];
        $mail->Password   = $config['smtp_pass'];
        $mail->SMTPSecure = $config['smtp_secure'] ?? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) ($config['smtp_port'] ?? 587);
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($config['from_email'], $config['from_name'] ?? '');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->isHTML(false);

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('send_email: ' . $e->getMessage());
        return false;
    }
}
