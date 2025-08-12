<?php
function parse_onu_rows($html,$ponHint=null){
  $dom = new DOMDocument(); libxml_use_internal_errors(true);
  $ok = $dom->loadHTML($html); libxml_clear_errors(); if(!$ok) return [];
  $tables=$dom->getElementsByTagName("table"); $target=null;
  foreach($tables as $tbl){ $tr0=$tbl->getElementsByTagName("tr")->item(0); if(!$tr0)continue;
    foreach($tr0->getElementsByTagName("td") as $td){
      $t=trim($td->textContent);
      if (strcasecmp($t,'ONU ID')===0 || stripos($t,'ONU ID')!==false){ $target=$tbl; break 2; }
    }
  }
  if(!$target) return [];
  $rows=[]; $trs=$target->getElementsByTagName("tr");
  for($i=1;$i<$trs->length;$i++){
    $tds=$trs->item($i)->getElementsByTagName("td"); if($tds->length<7) continue;
    $onuidDisp=trim($tds->item(0)->textContent);
    $status=trim($tds->item(1)->textContent);
    $desc=trim($tds->item(2)->textContent);
    $model=trim($tds->item(3)->textContent);
    $info=trim($tds->item(6)->textContent);
    $pon=$ponHint; $onu=null;
    if(preg_match('#/(\d+):(\d+)$#',$onuidDisp,$m)){ $pon=(int)$m[1]; $onu=(int)$m[2]; }
    $rows[]=[
      "pon"=>$pon,"onu"=>$onu,
      "onuid"=>$onuidDisp,"onuid_norm"=>norm_onuid($onuidDisp),
      "desc"=>$desc,"ndesc"=>norm_desc($desc),
      "model"=>$model,"info"=>$info,"status"=>$status,
      "key"=>($pon!==null&&$onu!==null)?"$pon-$onu":null
    ];
  }
  return $rows;
}
function parse_wan_status($html){
  $dom=new DOMDocument(); libxml_use_internal_errors(true);
  $ok=$dom->loadHTML($html); libxml_clear_errors(); if(!$ok) return null;
  foreach($dom->getElementsByTagName("table") as $tbl){
    $trs=$tbl->getElementsByTagName("tr"); if($trs->length<2) continue;
    $hdr=$trs->item(0)->getElementsByTagName("td"); $idx=null;
    for($i=0;$i<$hdr->length;$i++){
      if(stripos(trim($hdr->item($i)->textContent),'status')!==false){ $idx=$i; break; }
    }
    if($idx===null) continue;
    $row=$trs->item(1)->getElementsByTagName("td");
    if($row->length>$idx){ $s=trim($row->item($idx)->textContent); if($s!=='') return $s; }
  }
  return null;
}
function parse_optical_map($html,$ponContext=null){
  $dom=new DOMDocument(); libxml_use_internal_errors(true);
  $ok=$dom->loadHTML($html); libxml_clear_errors(); if(!$ok) return [];
  $out=[];
  foreach($dom->getElementsByTagName("table") as $tbl){
    $trs=$tbl->getElementsByTagName("tr"); if($trs->length<2) continue;
    $hdr=$trs->item(0)->getElementsByTagName("td"); if($hdr->length===0) continue;
    $idxOnu=null; $idxPon=null; $idxRx=null; $idxDisp=null;
    for($i=0;$i<$hdr->length;$i++){
      $t=trim($hdr->item($i)->textContent); $tl=mb_strtolower($t);
      if($idxOnu===null && (strcasecmp($t,'ONU ID')===0 || strpos($tl,'onu id')!==false)) $idxOnu=$i;
      if($idxPon===null && strpos($tl,'pon')!==false) $idxPon=$i;
      if($idxRx===null  && strpos($tl,'rx')!==false)  $idxRx=$i;
      if($idxDisp===null && (strpos($tl,'display')!==false || strpos($tl,'gpon')!==false)) $idxDisp=$i;
    }
    if($idxRx===null) continue;
    for($r=1;$r<$trs->length;$r++){
      $tds=$trs->item($r)->getElementsByTagName("td"); if($tds->length===0) continue;
      $onuidDisp=''; if($idxOnu!==null && $idxOnu<$tds->length) $onuidDisp=trim($tds->item($idxOnu)->textContent);
      elseif($idxDisp!==null && $idxDisp<$tds->length) $onuidDisp=trim($tds->item($idxDisp)->textContent);
      $pon=$ponContext; $onu=null;
      if($onuidDisp && preg_match('#/(\d+):(\d+)#',$onuidDisp,$m)){ $pon=(int)$m[1]; $onu=(int)$m[2]; }
      elseif($idxPon!==null && $idxPon<$tds->length){
        $t=trim($tds->item($idxPon)->textContent);
        if(preg_match('#/(\d+)#',$t,$m)) $pon=(int)$m[1]; elseif(is_numeric($t)) $pon=(int)$t;
      }
      $rxRaw=($idxRx!==null && $idxRx<$tds->length)?trim($tds->item($idxRx)->textContent):'';
      if(!preg_match('/-?\d+(\.\d+)?/',$rxRaw,$m)) continue;
      $out[]=["pon"=>$pon,"onu"=>$onu,"onuid"=>$onuidDisp,"onuid_norm"=>norm_onuid($onuidDisp),"rx"=>(float)$m[0]];
    }
  }
  return $out;
}
