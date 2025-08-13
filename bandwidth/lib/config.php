<?php
// Fill these with your details
return [
  'BASE'          => 'https://103.178.104.34:18292',
  'USERNAME'      => 'Chirag',
  'PASSWORD'      => 'Chirag@31',
  'PONS'          => range(1, 8),

  // timeouts / caches (seconds)
  'TIMEOUT'       => 15,
  'COOKIE_TTL'    => 300, // reuse one login cookie across API calls

  // (reserved for later if you want to log into SQLite/MySQL)
  'DB' => [
    'dsn'  => 'sqlite:' . __DIR__ . '/../data/bandwidth.sqlite',
    'user' => null,
    'pass' => null,
  ],
];
