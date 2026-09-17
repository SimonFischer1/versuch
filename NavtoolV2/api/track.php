<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/common.php';

header('Content-Type: application/json; charset=utf-8');
try {
    $path = substr((string)($_POST['path'] ?? '/'),0,500);
    $consent = !empty($_POST['consent']) ? 1 : 0;
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''),0,1000);
    $device=ua_device($ua); $browser=ua_browser($ua);
    $ref=clean_referrer($_SERVER['HTTP_REFERER'] ?? null);
    $vh=visitor_hash(); $sh=session_hash();
    $now=date('Y-m-d H:i:s');

    if (setting('analytics_enabled','1')==='1' && $consent) {
        $stmt=db()->prepare('INSERT INTO page_views
          (visited_at,path,referrer,device,browser,visitor_hash,session_hash,consent)
          VALUES(?,?,?,?,?,?,?,1)');
        $stmt->execute([$now,$path,$ref,$device,$browser,$vh,$sh]);
    }

    $stmt=db()->prepare('INSERT INTO live_visitors
      (session_hash,path,device,browser,referrer,last_seen)
      VALUES(?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE path=VALUES(path),device=VALUES(device),
      browser=VALUES(browser),referrer=VALUES(referrer),last_seen=VALUES(last_seen)');
    $stmt->execute([$sh,$path,$device,$browser,$ref,$now]);

    // Keep live data small.
    db()->exec("DELETE FROM live_visitors WHERE last_seen < (NOW() - INTERVAL 2 MINUTE)");

    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(['ok'=>false]);
}
