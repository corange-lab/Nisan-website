<?php
// /b/lib/config.php
//
// Base config for both local and server. Do NOT put secrets specific to one
// machine here—use config.local.php on that machine to override safely.

$defaults = [
  // OLT panel
  'BASE'     => 'https://103.178.104.34:18292',
  'USERNAME' => 'Chirag',
  'PASSWORD' => 'Chirag@31',

  // PON ports you want to scan
  'PONS'     => range(1, 8),

  // HTTP / session
  'TIMEOUT'     => 15,   // seconds
  'COOKIE_TTL'  => 300,  // seconds (reuse OLT login cookie)

  // Optional caches (kept for compatibility with older endpoints)
  'OPT_CACHE_TTL' => 10,
  'WAN_CACHE_TTL' => 10,

  // Database (local default). Server can override with config.local.php
  'DB' => [
    'dsn'  => 'sqlite:' . __DIR__ . '/../data/usage.sqlite',
    'user' => null,
    'pass' => null,
  ],

  // Retention knobs (used by retention.php if you run it)
  'RETENTION' => [
    'raw_days'    => 3,   // keep raw 3 days
    'rollup_days' => 30,  // keep daily rollups 30 days
  ],
];

// Allow machine-local overrides without committing secrets.
// Create /b/lib/config.local.php and return an array with keys you want to override.
$localFile = __DIR__ . '/config.local.php';
$overrides = (is_file($localFile) ? (require $localFile) : []);

return array_replace_recursive($defaults, is_array($overrides) ? $overrides : []);
