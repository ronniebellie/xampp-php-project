<?php
// Stub: serve jp-business break-even calculator only when host is jp-business.ronbelisle.com
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'jp-business.ronbelisle.com') {
    require __DIR__ . '/../jp-business.ronbelisle.com/breakeven-profit/index.php';
    return;
}
http_response_code(404);
exit('Not Found');
