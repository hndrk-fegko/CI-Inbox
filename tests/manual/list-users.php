<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../src/bootstrap/database.php';

$users = Illuminate\Database\Capsule\Manager::table('users')
    ->select('id', 'name', 'email')
    ->get();

echo "=== Users in Database ===\n";
foreach ($users as $user) {
    echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email}\n";
}
