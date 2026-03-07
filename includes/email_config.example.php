<?php
/**
 * Email (SMTP) configuration - COPY to email_config.php and fill in your values.
 * KEEP email_config.php OUT OF GIT!
 *
 * Options:
 *   SendGrid (free 100/day): smtp.sendgrid.net, port 587, user "apikey", pass = API key
 *   Mailgun: smtp.mailgun.org, port 587, user/pass from Mailgun dashboard
 *   Gmail: smtp.gmail.com, port 587, use App Password
 */
return [
    'smtp_host'   => 'smtp.sendgrid.net',
    'smtp_port'   => 587,
    'smtp_user'   => 'apikey',
    'smtp_pass'   => 'YOUR_SENDGRID_API_KEY',
    'from_email'  => 'noreply@calcforadvisors.com',
    'from_name'   => 'calcforadvisors.com',
    'smtp_secure' => 'tls',  // tls or ssl
];
