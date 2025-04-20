<?php

global $config;
require_once __DIR__ . '/modules/database.php';
require_once __DIR__ . '/modules/page.php';

require_once __DIR__ . '/config.php';

$db = new Database($config["db"]["path"]);

$page = new Page(__DIR__ . '/templates/index.tpl');

$pageId = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$data = $db->Read("page", $pageId);

if (!$data) {
    $data = [
        'title' => 'Page Not Found',
        'content' => 'The requested page does not exist.',
    ];
}

$data['current_year'] = date('Y');

echo $page->Render($data);