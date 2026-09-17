<?php
require_once __DIR__ . '/../includes/common.php';
$count=(int)db()->query('SELECT COUNT(*) c FROM admins')->fetch()['c'];
$msg=''; $err='';
if($count>0){ $msg='Die Einrichtung ist bereits abgeschlossen. Lösche setup.php nach der ersten Einrichtung.'; }
elseif($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $u=trim((string)($_POST['username']??''));
  $p=(string)($_POST['password']??'');
  if(strlen($u)<3 || strlen($p)<10) $err='Benutzername min. 3 Zeichen, Passwort min. 10 Zeichen.';
  else {
    db()->prepare('INSERT INTO admins(username,password_hash) VALUES(?,?)')->execute([$u,password_hash($p,PASSWORD_DEFAULT)]);
    $msg='Admin wurde erstellt. Lösche jetzt /admin/setup.php und melde dich an.';
  }
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NAVTOOL Setup</title><link rel="stylesheet" href="/assets/admin.css"></head><body class="admin-bg">
<div class="login-card"><div class="admin-logo">NT</div><div class="eyebrow">NAVTOOL · SETUP</div><h1>Erste Einrichtung</h1>
<?php if($msg):?><div class="alert success"><?=htmlspecialchars($msg)?></div><?php endif;?>
<?php if($err):?><div class="alert danger"><?=htmlspecialchars($err)?></div><?php endif;?>
<?php if($count===0):?><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
<label>Admin-Benutzername<input name="username" required></label><label>Admin-Passwort<input name="password" type="password" minlength="10" required></label><button class="primary">ADMIN ERSTELLEN</button></form><?php endif;?>
</div></body></html>