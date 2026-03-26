<?php
/**
 * Public participation card endpoint.
 *
 * This maps a cleaner URL filename to the existing API router action so browser tabs
 * show "participation-card.php" instead of "index.php".
 */
$_GET['module'] = 'share';
$_GET['action'] = 'shareCard';
require __DIR__ . '/index.php';
