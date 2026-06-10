<?php
require_once __DIR__ . '/config_bootstrap.php';

// Anthropic key is loaded from /etc/ronbelisle/config.php (prod) or environment variables.
// This file is intentionally committed (no secrets inside).
// If no key is found, ANTHROPIC_API_KEY stays undefined and AI Explain falls back to OpenAI.
$cfg = rb_config();
$anthropic = $cfg['anthropic'] ?? [];

rb_define('ANTHROPIC_API_KEY', $anthropic['api_key'] ?? rb_env('RB_ANTHROPIC_API_KEY'));
