<?php

/**
 * Migration: Add 'archived' status to threads table
 * 
 * Extends the status enum to include 'archived' option.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use CiInbox\Core\Container;

// Raw SQL to modify enum
$pdo = Capsule::connection()->getPdo();
$pdo->exec("ALTER TABLE threads MODIFY COLUMN status ENUM('open', 'pending', 'closed', 'archived') DEFAULT 'open'");

echo "✓ Added 'archived' to threads.status enum\n";
