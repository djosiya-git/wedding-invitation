<?php
function cfg(): array { static $c; return $c ??= require __DIR__.'/config.php'; }
function start_session(): void { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); }
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function app_logo_url(string $prefix = ''): string { return $prefix.'assets/brand/d-webin-logo.svg'; }
function app_favicon_tags(string $prefix = ''): string {
    $url = e(app_logo_url($prefix));
    return '<link rel="icon" href="'.$url.'" sizes="any" type="image/svg+xml">'."\n"
        .'<link rel="apple-touch-icon" href="'.$url.'">'."\n"
        .'<meta name="msapplication-TileImage" content="'.$url.'">';
}
function app_stylesheet_tags(string $prefix = ''): string {
    $path = __DIR__.'/assets/admin.css';
    $version = is_file($path) ? (string)filemtime($path) : (string)time();
    return '<link rel="stylesheet" href="'.e($prefix).'assets/admin.css?v='.e($version).'">'."\n".app_sidebar_script();
}
function app_sidebar_script(): string {
    return <<<'HTML'
<script>
(function () {
  try {
    if (localStorage.getItem('dwebinSidebarHidden') === '1') document.documentElement.classList.add('sidebar-hidden');
  } catch (e) {}
  window.dwebinToggleSidebar = function () {
    document.documentElement.classList.toggle('sidebar-hidden');
    try { localStorage.setItem('dwebinSidebarHidden', document.documentElement.classList.contains('sidebar-hidden') ? '1' : '0'); } catch (e) {}
  };
})();
</script>
HTML;
}
function app_logo_mark(string $class = 'logo', string $prefix = ''): string {
    $logo = '<div class="'.e($class).'"><img src="'.e(app_logo_url($prefix)).'" alt="D-Webin"></div>';
    if ($class !== 'logo') return $logo;
    return '<div class="brand-lockup">'.$logo.'<div class="brand-copy"><b>D-Webin Digital</b><span>Invitation</span></div></div><button class="sidebar-toggle" type="button" aria-label="Tampilkan atau sembunyikan sidebar" onclick="dwebinToggleSidebar()"><span></span><span></span><span></span></button>';
}
function default_whatsapp_message_template(): string {
    return "Tanpa mengurangi rasa hormat, perkenankan kami mengundang Bapak/Ibu/Saudara/i, {nama_tamu} untuk menghadiri acara pernikahan kami.\n\n"
        ."Berikut link undangan kami, untuk info lengkap dari acara, bisa kunjungi:\n\n"
        ."{link_undangan}\n\n"
        ."Merupakan suatu kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan untuk hadir dan memberikan doa restu.\n\n"
        ."Terima Kasih\n\n"
        ."Hormat kami,\n"
        ."{judul}";
}
function slugify(string $s): string {
    $s = strtolower(trim($s)); $s = preg_replace('/[^a-z0-9]+/','-',$s); return trim($s,'-') ?: 'undangan-'.time();
}
function templates(): array {
    $out = [];
    $categories = template_categories();
    $categoryOrder = array_flip(array_keys($categories));
    $discounts = active_template_discounts();
    foreach (glob(__DIR__.'/templates/*.html') ?: [] as $path) {
        $key = basename($path, '.html');
        if (!preg_match('/^([a-z]+)(?:-[a-z]+)*-(\d+)$/', $key, $m)) continue;
        $prefix = $m[1];
        $number = $m[2];
        $category = $categories[$prefix] ?? ucfirst($prefix);
        $nameBase = ucwords(str_replace('-', ' ', preg_replace('/-\d+$/', '', $key)));
        $basePrice = template_category_price($prefix);
        $discount = $discounts[$key] ?? null;
        $finalPrice = $discount ? max(0, (int)$discount['price']) : $basePrice;
        $out[$key] = [
            'name' => $nameBase.' '.$number,
            'file' => basename($path),
            'category' => $category,
            'category_key' => $prefix,
            'base_price' => $basePrice,
            'base_price_label' => $basePrice > 0 ? format_rupiah($basePrice) : 'Segera hadir',
            'price' => $finalPrice,
            'price_label' => $finalPrice > 0 ? format_rupiah($finalPrice) : 'Segera hadir',
            'has_discount' => (bool)$discount,
            'discount_until' => $discount['until'] ?? '',
            'discount_label' => $discount ? 'Diskon sampai '.format_datetime_label((string)$discount['until']) : '',
            'thumbnail_url' => template_thumbnail_url(basename($path)),
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
        'graduation' => 'Graduation',
        'corporate' => 'Corporate',
        'seminar' => 'Seminar',
        'vintage' => 'Vintage',
    ];
}
function template_category_prices(): array {
    $defaults = default_template_category_prices();
    try {
        $saved = app_setting('template_category_prices');
        if (is_array($saved)) {
            foreach ($defaults as $key => $price) $defaults[$key] = max(0, (int)($saved[$key] ?? $price));
        }
    } catch (Throwable $e) {
        error_log('Template prices fallback: '.$e->getMessage());
    }
    return $defaults;
}
function default_template_category_prices(): array {
    return [
        'animation' => 75000,
        'minimalist' => 90000,
        'luxury' => 0,
        'graduation' => 0,
        'corporate' => 0,
        'seminar' => 0,
        'vintage' => 105000,
    ];
}
function format_rupiah(int $amount): string {
    return 'Rp'.number_format($amount, 0, ',', '.');
}
function format_datetime_label(string $value): string {
    if ($value === '') return '';
    $time = strtotime($value);
    return $time ? date('d M Y H:i', $time) : $value;
}
function template_category_price(string $categoryKey): int {
    return template_category_prices()[$categoryKey] ?? 0;
}
function template_category_price_label(string $categoryKey): string {
    $price = template_category_price($categoryKey);
    return $price > 0 ? format_rupiah($price) : 'Segera hadir';
}
function template_thumbnail_url(string $templateFile): string {
    $key = basename($templateFile, '.html');
    $generated = __DIR__.'/assets/template-thumbs/'.$key.'.png';
    if (is_file($generated)) return 'assets/template-thumbs/'.$key.'.png';

    $path = __DIR__.'/templates/'.basename($templateFile);
    if (!is_file($path)) return '';
    $html = file_get_contents($path) ?: '';
    $candidates = [];
    if (preg_match('/"featuredImage"\s*:\s*"([^"]+)"/', $html, $m)) $candidates[] = stripcslashes($m[1]);
    if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) $candidates[] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    if (preg_match('/<img[^>]+src=["\']([^"\']+\.(?:webp|jpg|jpeg|png))(?:\?[^"\']*)?["\']/i', $html, $m)) $candidates[] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');

    foreach ($candidates as $url) {
        $resolved = local_template_asset_url($url);
        if ($resolved !== '') return $resolved;
    }
    return '';
}
function local_template_asset_url(string $url): string {
    $url = str_replace('\/', '/', $url);
    $parts = parse_url($url);
    $path = $parts['path'] ?? '';
    $uploads = '/wp-content/uploads/';
    $pos = strpos($path, $uploads);
    if ($pos === false) return '';
    $relative = substr($path, $pos + strlen($uploads));
    $relative = str_replace(['\\', '..'], ['/', ''], rawurldecode($relative));
    $assetPath = __DIR__.'/assets/template-assets/punakawan/uploads/'.$relative;
    if (!is_file($assetPath)) return '';
    return 'assets/template-assets/punakawan/uploads/'.implode('/', array_map('rawurlencode', explode('/', $relative)));
}
function templates_by_category(): array {
    $groups = [];
    foreach (template_categories() as $key => $label) {
        $groups[$key] = [
            'label' => $label,
            'price' => template_category_price($key),
            'price_label' => template_category_price_label($key),
            'templates' => [],
        ];
    }
    foreach (templates() as $key => $template) {
        $categoryKey = $template['category_key'] ?? strtolower($template['category']);
        if (!isset($groups[$categoryKey])) {
            $groups[$categoryKey] = [
                'label' => $template['category'],
                'price' => template_category_price($categoryKey),
                'price_label' => template_category_price_label($categoryKey),
                'templates' => [],
            ];
        }
        $groups[$categoryKey]['templates'][$key] = $template;
    }
    return $groups;
}
function app_setting(string $key, $default = null) {
    $stmt = db()->prepare('SELECT value FROM app_settings WHERE setting_key=?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    if ($value === false) return $default;
    $decoded = json_decode((string)$value, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
}
function save_app_setting(string $key, $value): void {
    $payload = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $now = date('c');
    if ((cfg()['db_driver'] ?? 'sqlite') === 'mysql') {
        $stmt = db()->prepare('INSERT INTO app_settings (setting_key,value,updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value), updated_at=VALUES(updated_at)');
    } else {
        $stmt = db()->prepare('INSERT INTO app_settings (setting_key,value,updated_at) VALUES (?,?,?) ON CONFLICT(setting_key) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at');
    }
    $stmt->execute([$key, $payload, $now]);
}
function template_discounts(): array {
    $saved = app_setting('template_discounts', []);
    return is_array($saved) ? $saved : [];
}
function active_template_discounts(?int $now = null): array {
    $now ??= time();
    $active = [];
    foreach (template_discounts() as $key => $discount) {
        if (!is_array($discount)) continue;
        $price = (int)($discount['price'] ?? 0);
        $until = trim((string)($discount['until'] ?? ''));
        $untilTime = $until !== '' ? strtotime($until) : false;
        if ($price <= 0 || !$untilTime || $untilTime < $now) continue;
        $active[$key] = ['price' => $price, 'until' => date('Y-m-d\TH:i', $untilTime)];
    }
    return $active;
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
        $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(120) PRIMARY KEY,
            value LONGTEXT NOT NULL,
            updated_at VARCHAR(40) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS invitations (
            slug VARCHAR(191) PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            template VARCHAR(100) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'draft',
            event_at VARCHAR(40) NULL,
            whatsapp_message_template LONGTEXT NULL,
            customer_username VARCHAR(120) NOT NULL DEFAULT '',
            customer_password_hash VARCHAR(255) NOT NULL DEFAULT '',
            guestbook_enabled TINYINT(1) NOT NULL DEFAULT 1,
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
            checked_in_at VARCHAR(40) NULL,
            checked_in_by VARCHAR(120) NOT NULL DEFAULT '',
            created_at VARCHAR(40) NOT NULL,
            updated_at VARCHAR(40) NOT NULL,
            CONSTRAINT fk_guests_invitation FOREIGN KEY(invitation_slug) REFERENCES invitations(slug) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
            setting_key TEXT PRIMARY KEY,
            value TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS invitations (
            slug TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            template TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'draft',
            event_at TEXT NOT NULL DEFAULT '',
            whatsapp_message_template TEXT NOT NULL DEFAULT '',
            customer_username TEXT NOT NULL DEFAULT '',
            customer_password_hash TEXT NOT NULL DEFAULT '',
            guestbook_enabled INTEGER NOT NULL DEFAULT 1,
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
            checked_in_at TEXT NOT NULL DEFAULT '',
            checked_in_by TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY(invitation_slug) REFERENCES invitations(slug) ON DELETE CASCADE
        )");
    }
    ensure_invitation_event_at_column($pdo, $driver);
    ensure_invitation_whatsapp_message_column($pdo, $driver);
    ensure_invitation_customer_columns($pdo, $driver);
    ensure_invitation_guestbook_column($pdo, $driver);
    ensure_guest_checkin_columns($pdo, $driver);
    if ($driver === 'mysql') {
        $stmt=$pdo->query("SHOW INDEX FROM guests WHERE Key_name='idx_guests_invitation'");
        if(!$stmt->fetch()) $pdo->exec("CREATE INDEX idx_guests_invitation ON guests(invitation_slug, name)");
    } else {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_guests_invitation ON guests(invitation_slug, name)");
    }
    if ($driver === 'mysql') migrate_sqlite_invitations($pdo);
    migrate_json_invitations($pdo);
}
function ensure_guest_checkin_columns(PDO $pdo, string $driver): void {
    if ($driver === 'mysql') {
        $stmt = $pdo->query("SHOW COLUMNS FROM guests LIKE 'checked_in_at'");
        if (!$stmt->fetch()) $pdo->exec("ALTER TABLE guests ADD checked_in_at VARCHAR(40) NULL AFTER status");
        $stmt = $pdo->query("SHOW COLUMNS FROM guests LIKE 'checked_in_by'");
        if (!$stmt->fetch()) $pdo->exec("ALTER TABLE guests ADD checked_in_by VARCHAR(120) NOT NULL DEFAULT '' AFTER checked_in_at");
        return;
    }
    $columns = [];
    foreach ($pdo->query("PRAGMA table_info(guests)") as $column) $columns[$column['name'] ?? ''] = true;
    if (empty($columns['checked_in_at'])) $pdo->exec("ALTER TABLE guests ADD COLUMN checked_in_at TEXT NOT NULL DEFAULT ''");
    if (empty($columns['checked_in_by'])) $pdo->exec("ALTER TABLE guests ADD COLUMN checked_in_by TEXT NOT NULL DEFAULT ''");
}
function ensure_invitation_event_at_column(PDO $pdo, string $driver): void {
    if ($driver === 'mysql') {
        $stmt = $pdo->query("SHOW COLUMNS FROM invitations LIKE 'event_at'");
        if (!$stmt->fetch()) $pdo->exec("ALTER TABLE invitations ADD event_at VARCHAR(40) NULL AFTER status");
        return;
    }
    $has = false;
    foreach ($pdo->query("PRAGMA table_info(invitations)") as $column) {
        if (($column['name'] ?? '') === 'event_at') {
            $has = true;
            break;
        }
    }
    if (!$has) $pdo->exec("ALTER TABLE invitations ADD COLUMN event_at TEXT NOT NULL DEFAULT ''");
}
function ensure_invitation_whatsapp_message_column(PDO $pdo, string $driver): void {
    if ($driver === 'mysql') {
        $stmt = $pdo->query("SHOW COLUMNS FROM invitations LIKE 'whatsapp_message_template'");
        if (!$stmt->fetch()) $pdo->exec("ALTER TABLE invitations ADD whatsapp_message_template LONGTEXT NULL AFTER event_at");
        return;
    }
    $has = false;
    foreach ($pdo->query("PRAGMA table_info(invitations)") as $column) {
        if (($column['name'] ?? '') === 'whatsapp_message_template') {
            $has = true;
            break;
        }
    }
    if (!$has) $pdo->exec("ALTER TABLE invitations ADD COLUMN whatsapp_message_template TEXT NOT NULL DEFAULT ''");
}
function ensure_invitation_customer_columns(PDO $pdo, string $driver): void {
    if ($driver === 'mysql') {
        $stmt = $pdo->query("SHOW COLUMNS FROM invitations LIKE 'customer_username'");
        if (!$stmt->fetch()) $pdo->exec("ALTER TABLE invitations ADD customer_username VARCHAR(120) NOT NULL DEFAULT '' AFTER whatsapp_message_template");
        $stmt = $pdo->query("SHOW COLUMNS FROM invitations LIKE 'customer_password_hash'");
        if (!$stmt->fetch()) $pdo->exec("ALTER TABLE invitations ADD customer_password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER customer_username");
        return;
    }
    $columns = [];
    foreach ($pdo->query("PRAGMA table_info(invitations)") as $column) $columns[$column['name'] ?? ''] = true;
    if (empty($columns['customer_username'])) $pdo->exec("ALTER TABLE invitations ADD COLUMN customer_username TEXT NOT NULL DEFAULT ''");
    if (empty($columns['customer_password_hash'])) $pdo->exec("ALTER TABLE invitations ADD COLUMN customer_password_hash TEXT NOT NULL DEFAULT ''");
}
function ensure_invitation_guestbook_column(PDO $pdo, string $driver): void {
    if ($driver === 'mysql') {
        $stmt = $pdo->query("SHOW COLUMNS FROM invitations LIKE 'guestbook_enabled'");
        if (!$stmt->fetch()) $pdo->exec("ALTER TABLE invitations ADD guestbook_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER customer_password_hash");
        return;
    }
    $columns = [];
    foreach ($pdo->query("PRAGMA table_info(invitations)") as $column) $columns[$column['name'] ?? ''] = true;
    if (empty($columns['guestbook_enabled'])) $pdo->exec("ALTER TABLE invitations ADD COLUMN guestbook_enabled INTEGER NOT NULL DEFAULT 1");
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
    $d['event_at'] = normalize_event_at($d['event_at'] ?? '');
    $d['whatsapp_message_template'] = trim((string)($d['whatsapp_message_template'] ?? '')) ?: default_whatsapp_message_template();
    $d['customer_username'] = trim((string)($d['customer_username'] ?? ''));
    $d['customer_password_hash'] = (string)($d['customer_password_hash'] ?? '');
    $d['guestbook_enabled'] = (int)($d['guestbook_enabled'] ?? 1) ? 1 : 0;
    return $d;
}
function normalize_event_at(?string $value): string {
    $value = trim((string)$value);
    if ($value === '') return '';
    $value = str_replace(' ', 'T', $value);
    if (preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})(?::\d{2})?$/', $value, $m)) return $m[1].'T'.$m[2];
    return '';
}
function parse_indonesian_event_at(string $value): string {
    $value = trim(html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8'));
    if ($value === '') return '';
    $months = [
        'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
        'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
        'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
    ];
    if (!preg_match('/(?:senin|selasa|rabu|kamis|jumat|jum\'at|sabtu|minggu)?\s*,?\s*(\d{1,2})\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\s+(\d{4})(?:.*?(\d{1,2})[:.](\d{2}))?/iu', $value, $m)) return '';
    $month = $months[strtolower($m[2])] ?? '';
    if ($month === '') return '';
    $hour = isset($m[4]) && $m[4] !== '' ? str_pad($m[4], 2, '0', STR_PAD_LEFT) : '00';
    $minute = isset($m[5]) && $m[5] !== '' ? $m[5] : '00';
    return sprintf('%04d-%s-%02dT%s:%s', (int)$m[3], $month, (int)$m[1], $hour, $minute);
}
function countdown_event_at_from_replacements(array $inv): string {
    foreach (($inv['replacements'] ?? []) as $r) {
        $eventAt = parse_indonesian_event_at((string)($r['to'] ?? ''));
        if ($eventAt !== '') return $eventAt;
    }
    return normalize_event_at($inv['event_at'] ?? '');
}
function load_invitation(string $slug): ?array {
    $stmt=db()->prepare('SELECT * FROM invitations WHERE slug=?'); $stmt->execute([$slug]);
    $d=$stmt->fetch(); return $d?normalize_invitation($d):null;
}
function save_invitation(array $d): void {
    $now=date('c'); $d['updated_at']=$now; if(empty($d['created_at']))$d['created_at']=$now;
    $d['event_at'] = normalize_event_at($d['event_at'] ?? '');
    $d['whatsapp_message_template'] = trim((string)($d['whatsapp_message_template'] ?? '')) ?: default_whatsapp_message_template();
    $d['customer_username'] = trim((string)($d['customer_username'] ?? ''));
    $d['customer_password_hash'] = (string)($d['customer_password_hash'] ?? '');
    $d['guestbook_enabled'] = (int)($d['guestbook_enabled'] ?? 1) ? 1 : 0;
    $sql='INSERT INTO invitations (slug,title,template,status,event_at,whatsapp_message_template,customer_username,customer_password_hash,guestbook_enabled,replacements,created_at,updated_at)
        VALUES (:slug,:title,:template,:status,:event_at,:whatsapp_message_template,:customer_username,:customer_password_hash,:guestbook_enabled,:replacements,:created_at,:updated_at)
        ON CONFLICT(slug) DO UPDATE SET title=excluded.title, template=excluded.template, status=excluded.status, event_at=excluded.event_at,
        whatsapp_message_template=excluded.whatsapp_message_template, customer_username=excluded.customer_username,
        customer_password_hash=excluded.customer_password_hash, guestbook_enabled=excluded.guestbook_enabled,
        replacements=excluded.replacements, updated_at=excluded.updated_at';
    if ((cfg()['db_driver'] ?? 'sqlite') === 'mysql') {
        $sql='INSERT INTO invitations (slug,title,template,status,event_at,whatsapp_message_template,customer_username,customer_password_hash,guestbook_enabled,replacements,created_at,updated_at)
            VALUES (:slug,:title,:template,:status,:event_at,:whatsapp_message_template,:customer_username,:customer_password_hash,:guestbook_enabled,:replacements,:created_at,:updated_at)
            ON DUPLICATE KEY UPDATE title=VALUES(title), template=VALUES(template), status=VALUES(status), event_at=VALUES(event_at),
            whatsapp_message_template=VALUES(whatsapp_message_template), customer_username=VALUES(customer_username),
            customer_password_hash=VALUES(customer_password_hash), guestbook_enabled=VALUES(guestbook_enabled),
            replacements=VALUES(replacements), updated_at=VALUES(updated_at)';
    }
    $stmt=db()->prepare($sql);
    $stmt->execute([
        ':slug'=>$d['slug'], ':title'=>$d['title'], ':template'=>$d['template'], ':status'=>$d['status']??'draft', ':event_at'=>$d['event_at'],
        ':whatsapp_message_template'=>$d['whatsapp_message_template'],
        ':customer_username'=>$d['customer_username'],
        ':customer_password_hash'=>$d['customer_password_hash'],
        ':guestbook_enabled'=>$d['guestbook_enabled'],
        ':replacements'=>json_encode($d['replacements']??[], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ':created_at'=>$d['created_at'], ':updated_at'=>$d['updated_at']
    ]);
}
function delete_invitation(string $slug): void {
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM guests WHERE invitation_slug=?');
    $stmt->execute([$slug]);
    $stmt = $pdo->prepare('DELETE FROM invitations WHERE slug=?');
    $stmt->execute([$slug]);
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
function guest_checkin_code(array $inv, array $guest): string {
    return $inv['slug'].'-'.(int)$guest['id'];
}
function guestbook_current_invitation(): ?array {
    start_session();
    $slug = (string)($_SESSION['customer_slug'] ?? '');
    return $slug !== '' ? load_invitation($slug) : null;
}
function invitation_guestbook_enabled(array $inv): bool {
    return (int)($inv['guestbook_enabled'] ?? 1) === 1;
}
function require_guestbook_customer(): array {
    $inv = guestbook_current_invitation();
    if (!$inv) {
        header('Location: login.php');
        exit;
    }
    if (!invitation_guestbook_enabled($inv)) {
        header('Location: ../guests.php?slug='.urlencode((string)$inv['slug']));
        exit;
    }
    return $inv;
}
function guestbook_stats(string $slug): array {
    $guests = all_guests($slug);
    $checkedIn = 0;
    foreach ($guests as $guest) {
        if (!empty($guest['checked_in_at'])) $checkedIn++;
    }
    return [
        'total' => count($guests),
        'checked_in' => $checkedIn,
        'remaining' => max(0, count($guests) - $checkedIn),
    ];
}
function parse_guest_scan_payload(string $payload, string $expectedSlug): int {
    $payload = trim($payload);
    if ($payload === '') return 0;
    $parts = parse_url($payload);
    if (!empty($parts['query'])) {
        parse_str((string)$parts['query'], $query);
        if (($query['slug'] ?? '') === $expectedSlug && !empty($query['guest'])) return (int)$query['guest'];
    }
    if (preg_match('/(?:^|[^a-z0-9])guest[=\/:-](\d+)/i', $payload, $m)) return (int)$m[1];
    if (preg_match('/^'.preg_quote($expectedSlug, '/').'-(\d+)$/', $payload, $m)) return (int)$m[1];
    if (preg_match('/^\d+$/', $payload)) return (int)$payload;
    return 0;
}
function mark_guest_checked_in(string $slug, int $guestId, string $operator): array {
    $guest = guest_by_id($slug, $guestId);
    if (!$guest) return ['ok' => false, 'status' => 'not_found', 'message' => 'Tamu tidak ditemukan.'];
    if (!empty($guest['checked_in_at'])) {
        return ['ok' => true, 'status' => 'already_checked_in', 'message' => 'Tamu sudah check-in sebelumnya.', 'guest' => $guest];
    }
    $now = date('c');
    $stmt = db()->prepare('UPDATE guests SET checked_in_at=?, checked_in_by=?, updated_at=? WHERE id=? AND invitation_slug=?');
    $stmt->execute([$now, $operator, $now, $guestId, $slug]);
    $guest = guest_by_id($slug, $guestId) ?: $guest;
    return ['ok' => true, 'status' => 'checked_in', 'message' => 'Check-in berhasil.', 'guest' => $guest];
}
function invitation_by_customer_username(string $username): ?array {
    $username = trim($username);
    if ($username === '') return null;
    $stmt=db()->prepare('SELECT * FROM invitations WHERE customer_username=? LIMIT 1');
    $stmt->execute([$username]);
    $d=$stmt->fetch(); return $d?normalize_invitation($d):null;
}
function is_customer_for_invitation(string $slug): bool {
    start_session();
    return !empty($_SESSION['customer_slug']) && hash_equals((string)$_SESSION['customer_slug'], $slug);
}
function require_invitation_access(string $slug): void {
    start_session();
    if (!empty($_SESSION['customer_slug'])) {
        if (is_customer_for_invitation($slug)) return;
        http_response_code(403);
        exit('Akses tamu tidak diizinkan untuk undangan ini.');
    }
    if (!empty($_SESSION['admin'])) return;
    header('Location: guestbook/login.php');
    exit;
}
function is_admin_user(): bool { start_session(); return !empty($_SESSION['admin']); }
function is_customer_user(): bool { start_session(); return !empty($_SESSION['customer_slug']); }
function public_base_url(): string {
    $base = rtrim(cfg()['base_url'] ?: '', '/');
    if ($base === '') return '';
    return preg_replace('~/admin$~i', '', $base) ?? $base;
}
function guest_link(array $inv, array $guest): string {
    $base=public_base_url();
    $path='view.php?slug='.urlencode($inv['slug']).'&guest='.(int)$guest['id'];
    return ($base ? $base.'/' : '').$path;
}
function normalize_whatsapp_phone(string $phone): string {
    $phone = preg_replace('/\D+/', '', $phone) ?? '';
    if ($phone === '') return '';
    if (substr($phone, 0, 1) === '0') return '62'.substr($phone, 1);
    if (substr($phone, 0, 1) === '8') return '62'.$phone;
    return $phone;
}
function guest_whatsapp_link(array $inv, array $guest): string {
    $phone = normalize_whatsapp_phone((string)($guest['phone'] ?? ''));
    if ($phone === '') return '';
    $message = render_whatsapp_message($inv, $guest);
    return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
}
function render_whatsapp_message(array $inv, array $guest): string {
    $template = trim((string)($inv['whatsapp_message_template'] ?? '')) ?: default_whatsapp_message_template();
    return strtr($template, [
        '{nama_tamu}' => trim((string)($guest['name'] ?? '')),
        '{nama}' => trim((string)($guest['name'] ?? '')),
        '{link_undangan}' => guest_link($inv, $guest),
        '{link}' => guest_link($inv, $guest),
        '{judul}' => trim((string)($inv['title'] ?? '')),
    ]);
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
    $replacements = $inv['replacements'] ?? [];
    usort($replacements, fn($a, $b) => strlen((string)($b['from'] ?? '')) <=> strlen((string)($a['from'] ?? '')));
    foreach($replacements as $r){
        if(!isset($r['from'],$r['to']) || $r['from']==='') continue;
        $type = $r['type'] ?? (preg_match('~^(?:https?:)?//|^storage/uploads/|^assets/|\\.(?:jpe?g|png|webp|gif|mp4|mp3|wav)(?:\\?|$)~i', (string)$r['from']) ? 'media' : 'text');
        if ($type === 'text') {
            $html = replace_template_text($html, (string)$r['from'], (string)$r['to']);
        } else {
            $html=str_replace((string)$r['from'], (string)$r['to'], $html);
        }
    }
    $guestName='';
    if(isset($_GET['guest'])){ $guest=guest_by_id($inv['slug'], (int)$_GET['guest']); if($guest)$guestName=$guest['name']; }
    if($guestName==='') $guestName=trim($_GET['to']??'');
    if($guestName!=='') $html=str_replace('Nama Tamu', e($guestName), $html);
    $html=apply_countdown_event($html, $inv);
    $html=apply_template_runtime_fixes($html);
    $html=str_replace('</head>', '<meta name="generator" content="D-Webin Invitation Manager"></head>', $html);
    return $html;
}
function format_template_text(string $value): string {
    return nl2br(e($value), false);
}
function replace_template_text(string $html, string $from, string $to): string {
    $replacement = format_template_text($to);
    $variants = array_values(array_unique([
        $from,
        html_entity_decode($from, ENT_QUOTES, 'UTF-8'),
        e(html_entity_decode($from, ENT_QUOTES, 'UTF-8')),
    ]));
    foreach ($variants as $variant) {
        if ($variant === '') continue;
        if (strpos($html, $variant) !== false) {
            $html = str_replace($variant, $replacement, $html);
        }
        $pattern = template_text_pattern($variant);
        if ($pattern !== '') {
            $html = preg_replace($pattern, $replacement, $html) ?? $html;
        }
    }
    return $html;
}
function template_text_pattern(string $text): string {
    $text = trim($text);
    if ($text === '') return '';
    $parts = preg_split('/\s+/u', $text) ?: [];
    $parts = array_values(array_filter($parts, fn($part) => $part !== ''));
    if (!$parts) return '';
    $joiner = '(?:\s|&nbsp;|&#160;|<br\s*/?>)+';
    return '~'.implode($joiner, array_map(fn($part) => preg_quote($part, '~'), $parts)).'~iu';
}
function apply_template_runtime_fixes(string $html): string {
    $script = <<<'HTML'
<script>
(function(){
  if (window.__DWEBIN_OPENING_VIDEO_FIX__) return;
  window.__DWEBIN_OPENING_VIDEO_FIX__ = true;

  function readSettings(el) {
    try { return JSON.parse(el.getAttribute('data-settings') || '{}'); }
    catch (e) { return {}; }
  }

  function setupOpeningVideos(playNow) {
    document.querySelectorAll('[data-settings*="background_video_link"]').forEach(function (section) {
      var settings = readSettings(section);
      var url = settings.background_video_link;
      if (!url) return;

      var video = section.querySelector('video.elementor-background-video-hosted');
      if (!video) return;

      video.muted = true;
      video.autoplay = true;
      video.playsInline = true;
      video.setAttribute('playsinline', '');
      video.setAttribute('muted', '');

      if (!video.getAttribute('src')) {
        video.setAttribute('src', url);
        video.load();
      }

      if (settings.background_video_start && !video.dataset.dwebinSeeked) {
        video.dataset.dwebinSeeked = '1';
        video.addEventListener('loadedmetadata', function () {
          try { video.currentTime = parseFloat(settings.background_video_start) || 0; } catch (e) {}
        }, { once: true });
      }

      if (playNow) {
        try {
          video.currentTime = settings.background_video_start ? (parseFloat(settings.background_video_start) || 0) : video.currentTime;
        } catch (e) {}
        var p = video.play();
        if (p && typeof p.catch === 'function') p.catch(function(){});
      }
    });
  }

  function setupLottieWidgets() {
    var player = window.lottie || window.bodymovin;
    if (!player || typeof player.loadAnimation !== 'function') return;

    document.querySelectorAll('.elementor-widget-lottie[data-settings]').forEach(function (widget) {
      var settings = readSettings(widget);
      var url = settings.source_json && settings.source_json.url;
      if (!url) return;

      var container = widget.querySelector('.e-lottie__animation');
      if (!container || container.dataset.dwebinLottieReady === '1') return;
      if (container.querySelector('svg, canvas')) {
        container.dataset.dwebinLottieReady = '1';
        return;
      }

      widget.classList.remove('elementor-invisible');
      container.dataset.dwebinLottieReady = '1';

      try {
        player.loadAnimation({
          container: container,
          renderer: settings.renderer || 'svg',
          loop: settings.loop !== 'no',
          autoplay: true,
          path: url
        });
      } catch (e) {
        container.dataset.dwebinLottieReady = '0';
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () { setupOpeningVideos(false); });
  window.addEventListener('load', setupLottieWidgets);
  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(setupLottieWidgets, 150);
    setTimeout(setupLottieWidgets, 900);
  });
  document.addEventListener('idbRevealStart', function () {
    setupOpeningVideos(true);
    setTimeout(setupLottieWidgets, 80);
    setTimeout(setupLottieWidgets, 700);
  });
  document.addEventListener('click', function (event) {
    if (event.target && event.target.closest && event.target.closest('#open')) {
      setTimeout(function () { setupOpeningVideos(true); }, 80);
      setTimeout(function () { setupOpeningVideos(true); }, 450);
      setTimeout(setupLottieWidgets, 120);
      setTimeout(setupLottieWidgets, 900);
    }
  }, true);
})();
</script>
HTML;
    if (strpos($html, '__DWEBIN_OPENING_VIDEO_FIX__') !== false) return $html;
    return str_ireplace('</body>', $script.'</body>', $html);
}
function apply_countdown_event(string $html, array $inv): string {
    $eventAt = countdown_event_at_from_replacements($inv);
    if ($eventAt === '') return $html;
    $iso = $eventAt.':00+07:00';
    $target = (string)(strtotime($iso) * 1000);
    $html = preg_replace_callback('/<div\b([^>]*\bclass=["\'][^"\']*\bidb-countdown\b[^"\']*["\'][^>]*)>/i', function ($m) use ($iso, $target) {
        $attrs = preg_replace('/\sdata-target=["\'][^"\']*["\']/i', '', $m[1]);
        $attrs = preg_replace('/\sdata-target-iso=["\'][^"\']*["\']/i', '', $attrs);
        return '<div'.$attrs.' data-target="'.e($target).'" data-target-iso="'.e($iso).'">';
    }, $html) ?? $html;
    $script = '<script>(function(){var target=new Date('.json_encode($iso).').getTime();function pad(n){return Math.max(0,Math.floor(n));}function tick(){document.querySelectorAll(".idb-countdown").forEach(function(box){box.setAttribute("data-target",String(target));box.setAttribute("data-target-iso",'.json_encode($iso).');var diff=Math.max(0,target-Date.now());var days=pad(diff/86400000);var hours=pad(diff%86400000/3600000);var minutes=pad(diff%3600000/60000);var seconds=pad(diff%60000/1000);var parts={days:days,hours:hours,minutes:minutes,seconds:seconds};Object.keys(parts).forEach(function(key){var num=box.querySelector("[data-part="+key+"] [data-role=num]");if(num)num.textContent=String(parts[key]);});});}tick();setInterval(tick,1000);})();</script>';
    return str_ireplace('</body>', $script.'</body>', $html);
}
