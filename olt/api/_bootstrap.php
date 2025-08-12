<?php
header('Content-Type: application/json; charset=utf-8');
/* Prevent caching of API responses */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$CFG = require __DIR__.'/../lib/config.php';
require __DIR__.'/../lib/util.php';
require __DIR__.'/../lib/cache.php';
require __DIR__.'/../lib/session.php';
require __DIR__.'/../lib/parsers.php';
if (!isset($_SESSION)) session_start();
