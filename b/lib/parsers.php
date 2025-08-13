<?php
// tolerant parser: group <td> cells by 5 when the first starts with GPON*
function parse_onu_statistics_html($html, $pon){
  $rows = [];
  $tableHtml = null;
  if (preg_match('~<table[^>]*border\s*=\s*["\']?1["\']?[^>]*>.*?ONU\s*ID.*?</table>~is', $html, $m)) {
    $tableHtml = $m[0];
  } else {
    $tableHtml = $html;
  }
  if (!preg_match_all('~<td[^>]*>\s*(.*?)\s*</td>~is', $tableHtml, $cells)) return $rows;
  $tds = array_map(function($v){ $v = trim(strip_tags($v)); return $v===''?null:$v; }, $cells[1]);

  $i=0; $n=count($tds);
  while ($i+4 < $n){
    $c0=$tds[$i]; $c1=$tds[$i+1]; $c2=$tds[$i+2]; $c3=$tds[$i+3]; $c4=$tds[$i+4];
    if ($c0!==null && preg_match('~^GPON~i',$c0)){
      $rows[] = [
        'pon'          => (int)$pon,
        'onuid'        => normalize_onuid($c0),
        'input_bytes'  => val_or_null($c1),
        'output_bytes' => val_or_null($c3),
      ];
      $i+=5;
    } else {
      $i+=1;
    }
  }
  return $rows;
}
