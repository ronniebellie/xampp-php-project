<?php
require_once __DIR__ . '/config_bootstrap.php';

// OpenAI key is loaded from /etc/ronbelisle/config.php (prod) or environment variables.
// This file is intentionally committed (no secrets inside).
$cfg = rb_config();
$openai = $cfg['openai'] ?? [];

rb_define('OPENAI_API_KEY', $openai['api_key'] ?? rb_env('RB_OPENAI_API_KEY'));
