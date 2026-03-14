<?php
// Language for jp-business: read from ?lang= or cookie, persist when switching
$lang = 'en';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ja'], true)) {
    setcookie('jp_business_lang', $_GET['lang'], time() + 86400 * 365, '/', '', false, true);
    $lang = $_GET['lang'];
} elseif (!empty($_COOKIE['jp_business_lang']) && $_COOKIE['jp_business_lang'] === 'ja') {
    $lang = 'ja';
}
