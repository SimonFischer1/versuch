<?php
require_once __DIR__ . '/../includes/common.php'; admin_required();
$pdo=db();
$today=$pdo->query("SELECT COUNT(*) c FROM page_views WHERE DATE(visited_at)=CURDATE()")->fetch()['c'];
$week=$pdo->query("SELECT COUNT(*) c FROM page_views WHERE visited_at>=NOW()-INTERVAL 7 DAY")->fetch()['c'];
$month=$pdo->query("SELECT COUNT(*) c FROM page_views WHERE visited_at>=NOW()-INTERVAL 30 DAY")->fetch()['c'];
$total=$pdo->query("SELECT COUNT(*) c FROM page_views")->fetch()['c'];
$live=$pdo->query("SELECT COUNT(*) c FROM live_visitors WHERE last_seen>=NOW()-INTERVAL 2 MINUTE")->fetch()['c'];
$devices=$pdo->query("SELECT device,COUNT(*) c FROM page_views WHERE visited_at>=NOW()-INTERVAL 30 DAY GROUP BY device ORDER BY c DESC")->fetchAll();
$pages=$pdo->query("SELECT path,COUNT(*) c FROM page_views WHERE visited_at>=NOW()-INTERVAL 30 DAY GROUP BY path ORDER BY c DESC LIMIT 10")->fetchAll();
$ads=$pdo->query("SELECT * FROM advertisements ORDER BY id DESC")->fetchAll();
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NAVTOOL Admin</title><link rel="stylesheet" href="/assets/admin.css"></head><body class="admin-bg">
<header class="admin-top"><a class="admin-brand" href="/"><span>NT</span>NAVTOOL ADMIN</a><div><span class="status <?=is_online()?'online':'offline'?>">● <?=is_online()?'ONLINE':'OFFLINE'?></span> <a class="ghost" href="/admin/logout.php">Abmelden</a></div></header>
<div class="admin-layout"><aside><a class="active" href="/admin/">Dashboard</a><a href="/admin/settings.php">Website & Module</a><a href="/admin/ads.php">Werbung</a><a href="/admin/visitors.php">Besucher</a></aside>
<main><div class="eyebrow">01 · OVERVIEW</div><h1>Dashboard</h1>
<div class="stats"><div><small>HEUTE</small><b><?=number_format((int)$today)?></b></div><div><small>7 TAGE</small><b><?=number_format((int)$week)?></b></div><div><small>30 TAGE</small><b><?=number_format((int)$month)?></b></div><div><small>GESAMT</small><b><?=number_format((int)$total)?></b></div><div><small>JETZT</small><b><?=number_format((int)$live)?></b></div></div>
<div class="two"><section class="panel"><h2>Geräte · letzte 30 Tage</h2><?php foreach($devices as $d):?><div class="row"><span><?=htmlspecialchars($d['device'])?></span><strong><?=number_format((int)$d['c'])?></strong></div><?php endforeach;?></section>
<section class="panel"><h2>Beliebte Seiten · 30 Tage</h2><?php foreach($pages as $p):?><div class="row"><span><?=htmlspecialchars($p['path'])?></span><strong><?=number_format((int)$p['c'])?></strong></div><?php endforeach;?></section></div>
<section class="panel"><div class="panel-head"><h2>Werbung</h2><a class="primary small" href="/admin/ads.php">VERWALTEN</a></div>
<?php if(!$ads):?><p class="muted">Noch keine Kampagnen angelegt.</p><?php else: foreach(array_slice($ads,0,5) as $a):?><div class="row"><span><?=htmlspecialchars($a['title'])?> · <?=htmlspecialchars($a['placement'])?></span><strong><?=((int)$a['active'])?'🟢':'🔴'?></strong></div><?php endforeach;endif;?></section>
</main></div></body></html>