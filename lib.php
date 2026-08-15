<?php
function cfg(): array { static $c; return $c ??= require __DIR__.'/config.php'; }
function start_session(): void { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); }
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function slugify(string $s): string {
    $s = strtolower(trim($s)); $s = preg_replace('/[^a-z0-9]+/','-',$s); return trim($s,'-') ?: 'undangan-'.time();
}
function templates(): array {
    return [
      'special-01'=>['name'=>'Special 01','file'=>'special-01.html','category'=>'Special'],
      'special-02'=>['name'=>'Special 02','file'=>'special-02.html','category'=>'Special'],
      'vintage-01'=>['name'=>'Vintage 05','file'=>'vintage-01.html','category'=>'Vintage'],
    ];
}
function invitation_path(string $slug): string { return __DIR__.'/storage/invitations/'.basename($slug).'.json'; }
function load_invitation(string $slug): ?array {
    $p=invitation_path($slug); if(!is_file($p)) return null; $d=json_decode(file_get_contents($p),true); return is_array($d)?$d:null;
}
function save_invitation(array $d): void {
    $d['updated_at']=date('c'); if(empty($d['created_at']))$d['created_at']=$d['updated_at'];
    file_put_contents(invitation_path($d['slug']), json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), LOCK_EX);
}
function all_invitations(): array {
    $out=[]; foreach(glob(__DIR__.'/storage/invitations/*.json')?:[] as $p){$d=json_decode(file_get_contents($p),true); if(is_array($d))$out[]=$d;}
    usort($out,fn($a,$b)=>strcmp($b['updated_at']??'',$a['updated_at']??'')); return $out;
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
    if(isset($_GET['to']) && trim($_GET['to'])!=='') $html=str_replace('Nama Tamu', e(trim($_GET['to'])), $html);
    $base=cfg()['base_url'];
    $html=str_replace('</head>', '<meta name="generator" content="D-Webin Invitation Manager"></head>', $html);
    return $html;
}
