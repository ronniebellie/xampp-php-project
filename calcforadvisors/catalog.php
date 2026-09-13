<?php
/** Public catalog: no account information or paid access is exposed. */
require_once __DIR__ . '/includes/init.php';
require_once CALCFORADVISORS_INCLUDES . '/calculator_catalog.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
echo json_encode(['count'=>count(rb_advisor_calculators()), 'calculators'=>array_values(rb_advisor_calculators())], JSON_UNESCAPED_SLASHES);
