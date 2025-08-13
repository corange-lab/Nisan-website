<?php
// Parse rows from onustatistics.html table into structured array
function parse_onu_statistics_html($html, $pon){
  $rows = [];

  // Fallback-friendly: scan every <tr>, pick those starting with GPON*
  if (!preg_match_all('~<tr[^>]*>\s*(.*?)\s*</tr>~is', $html, $trs)) {
    return $rows;
  }
  foreach ($trs[0] as $tr) {
    if (!preg_match_all('~<td[^>]*>\s*(.*?)\s*</td>~is', $tr, $cells)) continue;
    $td = array_map(function($v){ return trim(strip_tags($v)); }, $cells[1]);
    if (count($td) < 5) continue;

    $onuid = $td[0] ?? '';
    if (!preg_match('~^GPON~i', $onuid)) continue;

    $rows[] = [
      'pon'            => (int)$pon,
      'onuid'          => normalize_onuid($onuid),
      'input_bytes'    => val_or_null($td[1] ?? null),
      'input_packets'  => val_or_null($td[2] ?? null),
      'output_bytes'   => val_or_null($td[3] ?? null),
      'output_packets' => val_or_null($td[4] ?? null),
    ];
  }
  return $rows;
}
