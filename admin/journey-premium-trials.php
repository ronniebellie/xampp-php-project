<?php
// Backward-compatible redirect for saved bookmarks and older admin links.
header('Location: /admin/recent-signups.php', true, 301);
exit;
