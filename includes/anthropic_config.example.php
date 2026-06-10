<?php
// Anthropic (Claude) API Configuration
// 1. Copy this file to anthropic_config.php (kept out of Git via .gitignore), OR
//    add an 'anthropic' => ['api_key' => '...'] block to /etc/ronbelisle/config.php (prod),
//    OR set the RB_ANTHROPIC_API_KEY environment variable.
// 2. Get a key from https://console.anthropic.com/ (this is SEPARATE from your Cursor subscription).
// 3. Used by api/explain_results.php to power Claude Fable 5 explanations for the advisor tier.
//
// IMPORTANT: If no key is configured, the AI Explain feature automatically falls back
// to the cheaper OpenAI model. Claude Fable 5 stays dormant until a key is present.

define('ANTHROPIC_API_KEY', 'sk-ant-your-api-key-here');
