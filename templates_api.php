<?php
require __DIR__.'/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

$groups = [];
foreach (templates_by_category() as $key => $group) {
    $templates = [];
    foreach ($group['templates'] as $templateKey => $template) {
        $templates[] = [
            'key' => $templateKey,
            'name' => $template['name'],
            'category' => $template['category'],
            'category_key' => $template['category_key'],
            'price_label' => $template['price_label'],
            'thumbnail_url' => '/admin/'.$template['thumbnail_url'],
            'preview_url' => '/admin/template_preview.php?template='.rawurlencode($templateKey),
        ];
    }
    $groups[] = [
        'key' => $key,
        'label' => $group['label'],
        'price_label' => $group['price_label'],
        'template_count' => count($templates),
        'templates' => $templates,
    ];
}

echo json_encode([
    'brand' => 'D-Webin Digital Invitation',
    'logo_url' => '/admin/'.app_logo_url(),
    'template_count' => count(templates()),
    'groups' => $groups,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
