<?php
// Copy this file to config.local.php and edit values for this server.
return [
  'BASE' => 'https://YOUR-OLT-IP:PORT',
  'USERNAME' => 'YOUR_USERNAME',
  'PASSWORD' => 'YOUR_PASSWORD',
  // Set a strong secret to protect /olt/api/* endpoints.
  'API_KEY' => 'replace-with-strong-random-token',
  'DB' => [
    'dsn' => 'sqlite:' . __DIR__ . '/../data/olt.sqlite',
    'user' => null,
    'pass' => null,
  ],
];
