<?php
require_once __DIR__ . '/../includes/common.php'; admin_required();
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_check(); $action=$_POST['action']??'';
 if($action==='save'){
  $id=(int)($_POST['id']??0);
  $data=[trim((string)$_POST['title']),trim((string)$_POST['description']),trim((string)$_POST['image_url']),trim((string)$_POST['button_text']),trim((string)$_POST['target_url']),$_POST['placement']??'banner',isset($_POST['active'])?1:0,($_POST['start_at']??'')?:null,($_POST['end_at']??'')?:null,max(0,(int)($_POST['popup_delay']??5)),$_POST['popup_frequency']??'24h'];
  if($id){$s=db()->prepare('UPDATE advertisements SET title=?,description=?,image_url=?,button_text=?,target_url=?,placement=?,active=?,start_at=?,end_at=?,popup_delay=?,popup_frequency=? WHERE id=?');$s->execute([...$data,$id]);}
  else {$s=db()->prepare('INSERT INTO advertisements(title,description,image_url,button_text,target_url,placement,active,start_at,end_at,popup_delay,popup_frequency) VALUES(?,?,?,?,?,?,?,?,?,?,?)');$s->execute($data);}
  admin_log('ad_save','id='.$id); header('Location:/admin/ads.php');exit;
 }
 if($action==='toggle'){ $id=(int)$_POST['id']; db()->prepare('UPDATE advertisements SET active=1-active WHERE id=?')->execute([$id]); admin_log('ad_toggle','id='.$id); header('Location:/admin/ads.php');exit; }
 if($action==='delete'){ $id=(int)$_POST['id']; db()->prepare('DELETE FROM advertisements WHERE id=?')->execute([$id]); admin_log('ad_delete','id='.$id); header('Location:/admin/ads.php');exit; }
}
$edit=null;if(isset($_GET['edit'])){$s=db()->prepare('SELECT * FROM advertisements WHERE id=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$ads=db()->query('SELECT * FROM advertisements ORDER BY id DESC')->fetchAll();
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Werbung · NAVTOOL Admin</title><link rel="stylesheet" href="/assets/admin.css"></head><body class="admin-bg">
<header class="admin-top"><a class="admin-brand" href="/admin/"><span>NT</span>NAVTOOL ADMIN</a><a class="ghost" href="/admin/logout.php">Abmelden</a></header>
<div class="admin-layout"><aside><a href="/admin/">Dashboard</a><a href="/admin/settings.php">Website & Module</a><a class="active" href="/admin/ads.php">Werbung</a><a href="/admin/visitors.php">Besucher</a></aside>
<main><div class="eyebrow">03 · ADVERTISING</div><h1>Werbung</h1>
<section class="panel"><h2><?= $edit?'Werbung bearbeiten':'Neue Werbung'?></h2>
<form method="post" class="form"><input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=$edit['id']??0?>">
<label>Titel<input name="title" required value="<?=htmlspecialchars($edit['title']??'')?>"></label>
<label>Beschreibung<textarea name="description"><?=htmlspecialchars($edit['description']??'')?></textarea></label>
<label>Bild-URL<input name="image_url" value="<?=htmlspecialchars($edit['image_url']??'')?>" placeholder="/assets/ad.jpg"></label>
<label>Button-Text<input name="button_text" value="<?=htmlspecialchars($edit['button_text']??'Mehr erfahren')?>"></label>
<label>Ziel-URL<input name="target_url" value="<?=htmlspecialchars($edit['target_url']??'/')?>"></label>
<label>Position<select name="placement"><option value="banner" <?=($edit['placement']??'banner')==='banner'?'selected':''?>>Banner</option><option value="popup" <?=($edit['placement']??'')==='popup'?'selected':''?>>Popup</option><option value="both" <?=($edit['placement']??'')==='both'?'selected':''?>>Banner + Popup</option></select></label>
<label class="switch"><input type="checkbox" name="active" <?=!isset($edit)||$edit['active']?'checked':''?>> <span>Aktiv</span></label>
<div class="two"><label>Start<input type="datetime-local" name="start_at" value="<?=!empty($edit['start_at'])?date('Y-m-d\TH:i',strtotime($edit['start_at'])):''?>"></label><label>Ende<input type="datetime-local" name="end_at" value="<?=!empty($edit['end_at'])?date('Y-m-d\TH:i',strtotime($edit['end_at'])):''?>"></label></div>
<div class="two"><label>Popup-Verzögerung (Sek.)<input type="number" name="popup_delay" min="0" value="<?=htmlspecialchars($edit['popup_delay']??5)?>"></label><label>Popup-Häufigkeit<select name="popup_frequency"><option value="session">Einmal pro Sitzung</option><option value="24h" <?=($edit['popup_frequency']??'24h')==='24h'?'selected':''?>>Alle 24 Stunden</option><option value="always" <?=($edit['popup_frequency']??'24h')==='always'?'selected':''?>>Jeder Aufruf</option></select></label></div>
<button class="primary">SPEICHERN</button></form></section>
<section class="panel"><h2>Kampagnen</h2><?php foreach($ads as $a):?><div class="ad-row"><div><b><?=htmlspecialchars($a['title'])?></b><small><?=htmlspecialchars($a['placement'])?> · <?=htmlspecialchars($a['start_at']??'sofort')?> → <?=htmlspecialchars($a['end_at']??'offen')?></small></div><div class="actions"><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$a['id']?>"><button class="ghost"><?=((int)$a['active'])?'🟢 aktiv':'🔴 aus'?></button></form><a class="ghost" href="?edit=<?=$a['id']?>">Bearbeiten</a><form method="post" onsubmit="return confirm('Werbung löschen?')"><input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$a['id']?>"><button class="danger-btn">Löschen</button></form></div></div><?php endforeach;?></section>
</main></div></body></html>