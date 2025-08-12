<?php
header('Content-Type: application/json; charset=utf-8');
$CFG = require __DIR__.'/../lib/config.php';
require __DIR__.'/../lib/util.php';
require __DIR__.'/../lib/cache.php';
require __DIR__.'/../lib/session.php';
require __DIR__.'/../lib/parsers.php';
if (!isset($_SESSION)) session_start();
