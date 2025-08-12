<?php
// Fill these with your details
return [
  'BASE'          => 'https://103.178.104.34:18292',
  'USERNAME'      => 'Chirag',
  'PASSWORD'      => 'Chirag@31',
  'PONS'          => range(1, 8),

  // timeouts / caches (seconds)
  'TIMEOUT'       => 15,
  'WAN_TIMEOUT'   => 8,
  'OPT_TIMEOUT'   => 10,
  'OPT_CACHE_TTL' => 10,
  'WAN_CACHE_TTL' => 10,

  // reuse one login cookie across API calls
  'COOKIE_TTL'    => 300, // 5 minutes
];
