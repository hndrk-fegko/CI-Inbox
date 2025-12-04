<?php
require 'vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Models\Thread;

$container = Container::getInstance();
$container->get('database');

$threads = Thread::limit(5)->get(['id', 'subject']);

echo "Available Threads:\n";
foreach ($threads as $t) {
    echo $t->id . ': ' . substr($t->subject, 0, 50) . "\n";
}
