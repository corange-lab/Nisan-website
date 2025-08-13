<?php
function json_out($a){
  if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
  }
  echo json_encode($a, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * Normalize ONU ID text
 * - Uppercase
 * - Replace NBSP (UTF-8 \xC2\xA0) and any whitespace runs with a single space
 */
function normalize_onuid($s){
  $s = (string)$s;
  // Replace UTF-8 NBSP (hex C2 A0) with normal space
  $s = str_replace("\xC2\xA0", ' ', $s);
  // Collapse whitespace
  $s = preg_replace('/\s+/', ' ', $s);
  $s = strtoupper(trim($s));
  return $s;
}

/**
 * Convert a table cell string to int or null.
 * Keeps very large ints as string on 32-bit PHP.
 */
function val_or_null($s){
  if ($s===null) return null;
  $s = trim((string)$s);
  if ($s==='' || strcasecmp($s,'NULL')===0) return null;
  // Strip commas and spaces
  $s = str_replace([',',' '],'',$s);
  if (ctype_digit($s)) {
    if (PHP_INT_SIZE >= 8) return (int)$s; // 64-bit PHP safe
    return $s; // 32-bit PHP: keep as string to avoid overflow
  }
  return null;
}
