<?php
function json_out($a){
  if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache'); header('Expires: 0');
  }
  echo json_encode($a, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  exit;
}

function normalize_onuid($s){
  $s = (string)$s;
  $s = str_replace("\xC2\xA0", ' ', $s);     // NBSP to space
  $s = preg_replace('/\s+/', ' ', $s);
  return strtoupper(trim($s));
}

function val_or_null($s){
  if ($s===null) return null;
  $s = trim((string)$s);
  if ($s==='' || strcasecmp($s,'NULL')===0) return null;
  $s = str_replace([',',' '],'',$s);
  if (ctype_digit($s)) {
    if (PHP_INT_SIZE >= 8) return (int)$s; // 64-bit PHP
    return $s; // 32-bit: keep as string
  }
  return null;
}

function to_num($v){
  if ($v===null || $v==='') return null;
  if (is_int($v)) return $v;
  if (is_string($v) && ctype_digit($v)) {
    if (PHP_INT_SIZE >= 8) return (int)$v;
    return (float)$v; // OK for deltas
  }
  if (is_numeric($v)) return (float)$v;
  return null;
}

/** Parse GPON id → [pon_port, onu_number], e.g. "GPON0/7:23" => [7,23] */
function extract_port_onu_id($id){
  if (preg_match('~^GPON\d+/(\d+):(\d+)~i', $id, $m)) {
    return [ (int)$m[1], (int)$m[2] ];
  }
  return [0,0];
}
