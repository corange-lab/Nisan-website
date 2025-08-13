<?php
if (!isset($_SESSION)) session_start();
$CFG = require __DIR__.'/config.php';
require __DIR__.'/util.php';
require __DIR__.'/session.php';
require __DIR__.'/parsers.php';
