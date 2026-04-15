<?php
/**
 * Load Composer autoload for Next Gen API (PHPMailer, etc.).
 */
declare(strict_types=1);

$autoload = dirname(__DIR__) . "/vendor/autoload.php";
if (is_readable($autoload)) {
  require_once $autoload;
}
