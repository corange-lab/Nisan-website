<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$CFG = require __DIR__.'/config.php';
require __DIR__.'/util.php';
require __DIR__.'/cache.php';
require __DIR__.'/session.php';
require __DIR__.'/parsers.php';
require __DIR__.'/db.php'; // <-- ensure db() is always available

if (!isset($_SESSION)) session_start();
