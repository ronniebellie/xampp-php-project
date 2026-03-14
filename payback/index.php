<?php
// Stub: serve jp-business Payback & Discounted Payback calculator only when host is jp-business.ronbelisle.com
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'jp-business.ronbelisle.com') {
    require __DIR__ . '/../jp-business.ronbelisle.com/payback/index.php';
    return;
}
http_response_code(404);
exit('Not Found');
