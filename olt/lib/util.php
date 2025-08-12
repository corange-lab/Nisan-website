<?php
function norm_desc($s){ $s=(string)$s; $s=str_replace("\xC2\xA0",' ',$s); $s=preg_replace('/[_\-]+/u',' ',$s); $s=preg_replace('/\s+/u',' ',$s); return trim(mb_strtolower($s)); }
function norm_onuid($s){ $s=(string)$s; $s=str_replace("\xC2\xA0",' ',$s); $s=preg_replace('/\s+/u',' ',$s); return strtoupper(trim($s)); }
function tmp_path($name){ return sys_get_temp_dir().DIRECTORY_SEPARATOR.$name; }
function json_out($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
