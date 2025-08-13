<?php
function json_out($a){
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
  echo json_encode($a, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  exit;
}
function normalize_onuid($s){
  $s = strtoupper(trim(preg_replace('/\s+/',' ', str_replace("\u{00A0}", ' ', (string)$s))));
  return $s;
}
function val_or_null($s){
  if ($s===null) return null;
  $s = trim((string)$s);
  if ($s==='' || strcasecmp($s,'NULL')===0) return null;
  $s = str_replace([',',' '],'',$s);
  if (ctype_digit($s)) {
    if (PHP_INT_SIZE >= 8) return (int)$s; // 64-bit PHP safe
    return $s; // 32-bit PHP: keep as string
  }
  return null;
}
