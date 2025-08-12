<?php
function cache_get($key,$ttl){
  $f = tmp_path("oltcache_".md5($key).".json");
  if (!is_file($f) || filemtime($f)+$ttl < time()) return null;
  $raw = @file_get_contents($f); if ($raw===false) return null;
  $d = json_decode($raw, true);  return is_array($d) ? $d : null;
}
function cache_set($key,$val){
  $f = tmp_path("oltcache_".md5($key).".json");
  @file_put_contents($f, json_encode($val, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
