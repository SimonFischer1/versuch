<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/common.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_online()) {
    echo json_encode([
        'online'=>false,
        'title'=>setting('maintenance_title','Kurz offline'),
        'message'=>setting('maintenance_message','NAVTOOL ist momentan offline.')
    ]);
    exit;
}
$modules=[];
foreach(['map','colreg','navtext','calc','weather','crew'] as $m) $modules[$m]=setting('module_'.$m,'1')==='1';

$now=date('Y-m-d H:i:s');
$stmt=db()->prepare("SELECT * FROM advertisements
  WHERE active=1
  AND (start_at IS NULL OR start_at<=?)
  AND (end_at IS NULL OR end_at>=?)
  ORDER BY id DESC LIMIT 1");
$stmt->execute([$now,$now]);
$ad=$stmt->fetch() ?: null;

echo json_encode(['online'=>true,'modules'=>$modules,'advertisement'=>$ad], JSON_UNESCAPED_UNICODE);
