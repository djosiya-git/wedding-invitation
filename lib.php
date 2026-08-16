<?php
function cfg(): array { static $c; return $c ??= require __DIR__.'/config.php'; }
function start_session(): void { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); }
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function slugify(string $s): string {
    $s = strtolower(trim($s)); $s = preg_replace('/[^a-z0-9]+/','-',$s); return trim($s,'-') ?: 'undangan-'.time();
}
function templates(): array {
    $out = [];
    $categories = template_categories();
    $categoryOrder = array_flip(array_keys($categories));
    foreach (glob(__DIR__.'/templates/*.html') ?: [] as $path) {
        $key = basename($path, '.html');
        if (!preg_match('/^([a-z]+)-(\d+)$/', $key, $m)) continue;
        $prefix = $m[1];
        $number = $m[2];
        $category = $categories[$prefix] ?? ucfirst($prefix);
        $out[$key] = [
            'name' => $category.' '.$number,
            'file' => basename($path),
            'category' => $category,
            'category_key' => $prefix,
            'sort' => str_pad((string)(($categoryOrder[$prefix] ?? 99) + 1), 2, '0', STR_PAD_LEFT).'-'.$number,
        ];
    }
    uasort($out, fn($a, $b) => strcmp($a['sort'], $b['sort']));
    foreach ($out as &$template) unset($template['sort']);
    return $out;
}
function template_categories(): array {
    return [
        'animation' => 'Animation',
        'minimalist' => 'Minimalist',
        'luxury' => 'Luxury',
        'vintage' => 'Vintage',
    ];
}
function templates_by_category(): array {
    $groups = [];
    foreach (template_categories() as $key => $label) $groups[$key] = ['label' => $label, 'templates' => []];
    foreach (templates() as $key => $template) {
        $categoryKey = $template['category_key'] ?? strtolower($template['category']);
        if (!isset($groups[$categoryKey])) $groups[$categoryKey] = ['label' => $template['category'], 'templates' => []];
        $groups[$categoryKey]['templates'][$key] = $template;
    }
    return $groups;
}
function normalize_template_key(string $key): string {
    if (preg_match('/^special-(\d+)$/', $key, $m)) return 'minimalist-'.$m[1];
    if (preg_match('/^animasi-(\d+)$/', $key, $m)) return 'animation-'.$m[1];
    return $key;
}
function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    $c = cfg();
    $driver = $c['db_driver'] ?? 'sqlite';
    if ($driver === 'mysql') {
        $charset = $c['db_charset'] ?? 'utf8mb4';
        $dsn = 'mysql:host='.($c['db_host'] ?? 'localhost').';dbname='.($c['db_name'] ?? '').';charset='.$charset;
        $pdo = new PDO($dsn, $c['db_username'] ?? '', $c['db_password'] ?? '');
    } else {
        $dir = __DIR__.'/storage';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $pdo = new PDO('sqlite:'.$dir.'/database.sqlite');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    init_db($pdo, $driver);
    return $pdo;
}
function init_db(PDO $pdo, string $driver): void {
    static $done = false; if ($done) return; $done = true;
    if ($driver === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS invitations (
            slug VARCHAR(191) PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            template VARCHAR(100) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'draft',
            replacements LONGTEXT NOT NULL,
            created_at VARCHAR(40) NOT NULL,
            updated_at VARCHAR(40) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS guests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invitation_slug VARCHAR(191) NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(80) NOT NULL DEFAULT '',
            group_label VARCHAR(120) NOT NULL DEFAULT '',
            note TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            created_at VARCHAR(40) NOT NULL,
            updated_at VARCHAR(40) NOT NULL,
            CONSTRAINT fk_guests_invitation FOREIGN KEY(invitation_slug) REFERENCES invitations(slug) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS invitations (
            slug TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            template TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'draft',
            replacements TEXT NOT NULL DEFAULT '[]',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS guests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invitation_slug TEXT NOT NULL,
            name TEXT NOT NULL,
            phone TEXT NOT NULL DEFAULT '',
            group_label TEXT NOT NULL DEFAULT '',
            note TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'pending',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY(invitation_slug) REFERENCES invitations(slug) ON DELETE CASCADE
        )");
    }
    if ($driver === 'mysql') {
        $stmt=$pdo->query("SHOW INDEX FROM guests WHERE Key_name='idx_guests_invitation'");
        if(!$stmt->fetch()) $pdo->exec("CREATE INDEX idx_guests_invitation ON guests(invitation_slug, name)");
    } else {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_guests_invitation ON guests(invitation_slug, name)");
    }
    if ($driver === 'mysql') migrate_sqlite_invitations($pdo);
    migrate_json_invitations($pdo);
}
function invitation_path(string $slug): string { return __DIR__.'/storage/invitations/'.basename($slug).'.json'; }
function migrate_sqlite_invitations(PDO $pdo): void {
    $path=__DIR__.'/storage/database.sqlite';
    if(!is_file($path)) return;
    try {
        $old=new PDO('sqlite:'.$path);
        $old->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $old->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $tables=$old->query("SELECT name FROM sqlite_master WHERE type='table' AND name='invitations'")->fetch();
        if(!$tables) return;
        foreach($old->query('SELECT * FROM invitations') as $d){
            if(empty($d['slug'])) continue;
            $exists=$pdo->prepare('SELECT 1 FROM invitations WHERE slug=?'); $exists->execute([$d['slug']]);
            if(!$exists->fetchColumn()){
                $stmt=$pdo->prepare('INSERT INTO invitations (slug,title,template,status,replacements,created_at,updated_at) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$d['slug'],$d['title'],$d['template'],$d['status'],$d['replacements'],$d['created_at'],$d['updated_at']]);
            }
        }
        $hasGuests=$old->query("SELECT name FROM sqlite_master WHERE type='table' AND name='guests'")->fetch();
        if(!$hasGuests) return;
        foreach($old->query('SELECT * FROM guests') as $g){
            $exists=$pdo->prepare('SELECT 1 FROM guests WHERE invitation_slug=? AND name=? AND phone=?');
            $exists->execute([$g['invitation_slug'],$g['name'],$g['phone']]);
            if($exists->fetchColumn()) continue;
            $stmt=$pdo->prepare('INSERT INTO guests (invitation_slug,name,phone,group_label,note,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$g['invitation_slug'],$g['name'],$g['phone'],$g['group_label'],$g['note'],$g['status'],$g['created_at'],$g['updated_at']]);
        }
    } catch (Throwable $e) {
        error_log('SQLite migration skipped: '.$e->getMessage());
    }
}
function migrate_json_invitations(PDO $pdo): void {
    foreach(glob(__DIR__.'/storage/invitations/*.json')?:[] as $p){
        $d=json_decode(file_get_contents($p),true); if(!is_array($d) || empty($d['slug'])) continue;
        $exists=$pdo->prepare('SELECT 1 FROM invitations WHERE slug=?'); $exists->execute([$d['slug']]);
        if($exists->fetchColumn()) continue;
        $now=date('c');
        $stmt=$pdo->prepare('INSERT INTO invitations (slug,title,template,status,replacements,created_at,updated_at) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            $d['slug'], $d['title']??$d['slug'], $d['template']??'minimalist-01', $d['status']??'draft',
            json_encode($d['replacements']??[], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            $d['created_at']??$now, $d['updated_at']??$now
        ]);
    }
}
function normalize_invitation(array $d): array {
    if (isset($d['replacements']) && is_string($d['replacements'])) {
        $decoded=json_decode($d['replacements'], true);
        $d['replacements']=is_array($decoded)?$decoded:[];
    }
    return $d;
}
function load_invitation(string $slug): ?array {
    $stmt=db()->prepare('SELECT * FROM invitations WHERE slug=?'); $stmt->execute([$slug]);
    $d=$stmt->fetch(); return $d?normalize_invitation($d):null;
}
function save_invitation(array $d): void {
    $now=date('c'); $d['updated_at']=$now; if(empty($d['created_at']))$d['created_at']=$now;
    $sql='INSERT INTO invitations (slug,title,template,status,replacements,created_at,updated_at)
        VALUES (:slug,:title,:template,:status,:replacements,:created_at,:updated_at)
        ON CONFLICT(slug) DO UPDATE SET title=excluded.title, template=excluded.template, status=excluded.status,
        replacements=excluded.replacements, updated_at=excluded.updated_at';
    if ((cfg()['db_driver'] ?? 'sqlite') === 'mysql') {
        $sql='INSERT INTO invitations (slug,title,template,status,replacements,created_at,updated_at)
            VALUES (:slug,:title,:template,:status,:replacements,:created_at,:updated_at)
            ON DUPLICATE KEY UPDATE title=VALUES(title), template=VALUES(template), status=VALUES(status),
            replacements=VALUES(replacements), updated_at=VALUES(updated_at)';
    }
    $stmt=db()->prepare($sql);
    $stmt->execute([
        ':slug'=>$d['slug'], ':title'=>$d['title'], ':template'=>$d['template'], ':status'=>$d['status']??'draft',
        ':replacements'=>json_encode($d['replacements']??[], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ':created_at'=>$d['created_at'], ':updated_at'=>$d['updated_at']
    ]);
}
function all_invitations(): array {
    $rows=db()->query("SELECT i.*, COUNT(g.id) guest_count FROM invitations i LEFT JOIN guests g ON g.invitation_slug=i.slug GROUP BY i.slug ORDER BY i.updated_at DESC")->fetchAll();
    return array_map('normalize_invitation', $rows);
}
function all_guests(string $slug): array {
    $order=(cfg()['db_driver'] ?? 'sqlite') === 'mysql' ? 'name' : 'name COLLATE NOCASE';
    $stmt=db()->prepare('SELECT * FROM guests WHERE invitation_slug=? ORDER BY '.$order);
    $stmt->execute([$slug]); return $stmt->fetchAll();
}
function save_guest(string $slug, array $d): void {
    $now=date('c');
    if (!empty($d['id'])) {
        $stmt=db()->prepare('UPDATE guests SET name=?, phone=?, group_label=?, note=?, status=?, updated_at=? WHERE id=? AND invitation_slug=?');
        $stmt->execute([$d['name'],$d['phone'],$d['group_label'],$d['note'],$d['status'],$now,$d['id'],$slug]);
        return;
    }
    $stmt=db()->prepare('INSERT INTO guests (invitation_slug,name,phone,group_label,note,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$slug,$d['name'],$d['phone'],$d['group_label'],$d['note'],$d['status'],$now,$now]);
}
function delete_guest(string $slug, int $id): void {
    $stmt=db()->prepare('DELETE FROM guests WHERE id=? AND invitation_slug=?'); $stmt->execute([$id,$slug]);
}
function guest_by_id(string $slug, int $id): ?array {
    $stmt=db()->prepare('SELECT * FROM guests WHERE id=? AND invitation_slug=?'); $stmt->execute([$id,$slug]);
    $d=$stmt->fetch(); return $d?:null;
}
function guest_link(array $inv, array $guest): string {
    $base=rtrim(cfg()['base_url'] ?: '', '/');
    $path='view.php?slug='.urlencode($inv['slug']).'&guest='.(int)$guest['id'];
    return ($base ? $base.'/' : '').$path;
}
function require_login(): void { start_session(); if(empty($_SESSION['admin'])){header('Location: login.php');exit;} }
function scan_template(string $templateKey): array {
    $t=templates()[$templateKey]??null; if(!$t) return ['texts'=>[],'images'=>[],'links'=>[],'videos'=>[]];
    $html=file_get_contents(__DIR__.'/templates/'.$t['file']);
    $clean=preg_replace('~<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>~is','',$html);
    $texts=[];
    if(preg_match_all('~>([^<>]{2,500})<~u',$clean,$m)){
      foreach($m[1] as $raw){ $v=trim(preg_replace('/\s+/u',' ',$raw)); if($v===''||preg_match('/^[\W_]+$/u',html_entity_decode($v)))continue; $texts[$v]=$v; }
    }
    $images=[]; if(preg_match_all('~<img\b[^>]*\bsrc=["\']([^"\']+)["\']~i',$html,$m))foreach($m[1] as $v)$images[$v]=$v;
    $links=[]; if(preg_match_all('~<a\b[^>]*\bhref=["\']([^"\']+)["\']~i',$html,$m))foreach($m[1] as $v)if($v!=='#')$links[$v]=$v;
    $videos=[]; if(preg_match_all('~<(?:video|source)\b[^>]*\bsrc=["\']([^"\']+)["\']~i',$html,$m))foreach($m[1] as $v)$videos[$v]=$v;
    return ['texts'=>array_values($texts),'images'=>array_values($images),'links'=>array_values($links),'videos'=>array_values($videos)];
}
function apply_replacements(string $html,array $inv): string {
    foreach(($inv['replacements']??[]) as $r){ if(isset($r['from'],$r['to']) && $r['from']!=='') $html=str_replace($r['from'],$r['to'],$html); }
    $guestName='';
    if(isset($_GET['guest'])){ $guest=guest_by_id($inv['slug'], (int)$_GET['guest']); if($guest)$guestName=$guest['name']; }
    if($guestName==='') $guestName=trim($_GET['to']??'');
    if($guestName!=='') $html=str_replace('Nama Tamu', e($guestName), $html);
    $html=str_replace('</head>', '<meta name="generator" content="D-Webin Invitation Manager"></head>', $html);
    return $html;
}
