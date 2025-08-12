<?php
require __DIR__.'/_bootstrap.php';
require __DIR__.'/../lib/db.php';

$hours = isset($_GET['hours']) ? max(1, (int)$_GET['hours']) : 24;
$since = time() - ($hours * 3600);

$pdo = db();
$q = $pdo->prepare("
  SELECT onuid_norm, AVG(rx) AS avg_rx, COUNT(*) AS cnt
  FROM rx_samples
  WHERE ts >= :since
  GROUP BY onuid_norm
");
$q->execute([':since'=>$since]);
$rows = $q->fetchAll();

$out = [];
foreach ($rows as $r){
  $out[$r['onuid_norm']] = [
    'avg' => (float)$r['avg_rx'],
    'cnt' => (int)$r['cnt']
  ];
}
json_out(['ok'=>true,'since'=>$since,'hours'=>$hours,'avg'=>$out]);
