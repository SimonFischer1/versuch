<?php
require_once __DIR__ . '/../includes/common.php';
if (!empty($_SESSION['admin_id'])) { header('Location: /admin/'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $u=trim((string)($_POST['username']??''));
    $p=(string)($_POST['password']??'');
    $stmt=db()->prepare('SELECT * FROM admins WHERE username=? LIMIT 1');
    $stmt->execute([$u]); $a=$stmt->fetch();
    if ($a && password_verify($p,$a['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']=(int)$a['id'];
        $_SESSION['admin_username']=$a['username'];
        db()->prepare('UPDATE admins SET last_login_at=NOW() WHERE id=?')->execute([$a['id']]);
        admin_log('login');
        header('Location: /admin/'); exit;
    }
    $error='Benutzername oder Passwort ist falsch.';
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>NAVTOOL Admin</title><link rel="stylesheet" href="/assets/admin.css"></head><body class="admin-bg">
<div class="login-card"><div class="admin-logo">NT</div><div class="eyebrow">NAVTOOL · ADMIN</div><h1>Anmelden</h1>
<?php if($error):?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
<label>Benutzername<input name="username" autocomplete="username" required></label>
<label>Passwort<input name="password" type="password" autocomplete="current-password" required></label>
<button class="primary">ADMIN PANEL ÖFFNEN →</button></form>
</div></body></html>