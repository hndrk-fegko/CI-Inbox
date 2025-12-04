<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use CiInbox\Core\Container;

$container = Container::getInstance();
$pdo = $container->get('database')->getConnection()->getPdo();

$ids = $pdo->query("SELECT id FROM threads WHERE status = 'open' ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);

echo "Existing Thread IDs for testing: " . implode(', ', $ids) . "\n";
