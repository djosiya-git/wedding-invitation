<?php
require __DIR__.'/../lib.php';
start_session();
header('Content-Type: application/json; charset=utf-8');

$inv = guestbook_current_invitation();
if (!$inv) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesi guestbook berakhir. Silakan login kembali.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method tidak didukung.']);
    exit;
}

$payload = trim((string)($_POST['payload'] ?? ''));
$guestId = parse_guest_scan_payload($payload, $inv['slug']);
if ($guestId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Kode undangan tidak valid untuk akun ini.']);
    exit;
}

$result = mark_guest_checked_in($inv['slug'], $guestId, (string)($_SESSION['guestbook_username'] ?? $inv['customer_username'] ?? 'guestbook'));
if (!$result['ok']) http_response_code(404);
$guest = $result['guest'] ?? [];
echo json_encode([
    'ok' => $result['ok'],
    'status' => $result['status'],
    'message' => $result['message'],
    'guest' => [
        'id' => (int)($guest['id'] ?? 0),
        'name' => (string)($guest['name'] ?? ''),
        'group_label' => (string)($guest['group_label'] ?? ''),
        'checked_in_at' => (string)($guest['checked_in_at'] ?? ''),
        'checked_in_label' => !empty($guest['checked_in_at']) ? format_datetime_label((string)$guest['checked_in_at']) : '',
    ],
    'stats' => guestbook_stats($inv['slug']),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
