<?php
// Fill these with your details
return [
  'BASE'          => 'https://103.178.104.34:18292',
  'USERNAME'      => 'Chirag',
  'PASSWORD'      => 'Chirag@31',
  'PONS'          => range(1, 8),

 // network/session
  'TIMEOUT'    => 15,
  'COOKIE_TTL' => 300, // reuse login 5 minutes

  // Database (SQLite)
  'DB' => [
    'dsn'  => 'sqlite:' . __DIR__ . '/../data/usage.sqlite',
    'user' => null,
    'pass' => null,
  ],
];