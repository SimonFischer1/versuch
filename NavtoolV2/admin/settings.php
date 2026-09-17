<?php
require_once __DIR__ . '/../includes/common.php'; admin_required();
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_check();
 set_setting('site_online',isset($_POST['site_online'])?'1':'0');
 set_setting('maintenance_title',trim((string)$_POST['maintenance_title']));
 set_setting('maintenance_message',trim((string)$_POST['maintenance_message']));
 set_setting('analytics_enabled',isset($_POST['analytics_enabled'])?'1':'0');
 set_setting('analytics_retention_days',max(7,min(3650,(int)$_POST['retention'])));
 foreach(['map','colreg','navtext','calc','weather','crew'] as $m) set_setting('module_'.$m,isset($_POST['module_'.$m])?'1':'0');
 admin_log('settings_update');
 header('Location: /admin/settings.php?saved=1'); exit;
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Website · NAVTOOL Admin</title><link rel="stylesheet" href="/assets/admin.css"></head><body class="admin-bg">
<header class="admin-top"><a class="admin-brand" href="/admin/"><span>NT</span>NAVTOOL ADMIN</a><a class="ghost" href="/admin/logout.php">Abmelden</a></header>
<div class="admin-layout"><aside><a href="/admin/">Dashboard</a><a class="active" href="/admin/settings.php">Website & Module</a><a href="/admin/ads.php">Werbung</a><a href="/admin/visitors.php">Besucher</a></aside>
<main><div class="eyebrow">02 · SETTINGS</div><h1>Website & Module</h1><?php if(isset($_GET['saved'])):?><div class="alert success">Gespeichert.</div><?php endif;?>
<form method="post" class="panel form"><input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
<h2>Website</h2><label class="switch"><input type="checkbox" name="site_online" <?=is_online()?'checked':''?>> <span>Website online</span></label>
<label>Offline-Titel<input name="maintenance_title" value="<?=htmlspecialchars(setting('maintenance_title','Kurz offline'))?>"></label>
<label>Offline-Nachricht<textarea name="maintenance_message"><?=htmlspecialchars(setting('maintenance_message','NAVTOOL ist momentan offline.'))?></textarea></label>
<h2>Statistik</h2><label class="switch"><input type="checkbox" name="analytics_enabled" <?=setting('analytics_enabled','1')==='1'?'checked':''?>> <span>Anonyme Statistik aktiv</span></label>
<label>Aufbewahrung (Tage)<input type="number" name="retention" min="7" max="3650" value="<?=htmlspecialchars(setting('analytics_retention_days','180'))?>"></label>
<h2>Module</h2><div class="checks"><?php foreach(['map'=>'MAP','colreg'=>'COLREG','navtext'=>'NAVTEXT','calc'=>'CALC','weather'=>'WEATHER','crew'=>'CREW'] as $k=>$v):?><label class="switch"><input type="checkbox" name="module_<?=$k?>" <?=setting('module_'.$k,'1')==='1'?'checked':''?>> <span><?=$v?></span></label><?php endforeach;?></div>
<button class="primary">ÄNDERUNGEN SPEICHERN</button></form></main></div></body></html>