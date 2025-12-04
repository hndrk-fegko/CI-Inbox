<?php
/**
 * Check threads table structure
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Core\Container;
use Illuminate\Database\Capsule\Manager as Capsule;

$container = Container::getInstance();
$container->get('database');

echo "🔍 Checking threads table structure...\n\n";

// Check status column
$columns = Capsule::select("SHOW COLUMNS FROM threads WHERE Field = 'status'");

if (count($columns) > 0) {
    $statusCol = $columns[0];
    echo "✅ Status column found:\n";
    echo "   Type: {$statusCol->Type}\n";
    echo "   Default: {$statusCol->Default}\n\n";
} else {
    echo "❌ Status column not found!\n\n";
}

// Check for other new columns
echo "📋 Recent migrations check:\n\n";

// Check sender fields (migration 010)
$senderCols = Capsule::select("SHOW COLUMNS FROM threads WHERE Field IN ('sender_name', 'sender_email')");
echo "Migration 010 (sender fields): " . (count($senderCols) === 2 ? "✅ Applied" : "❌ Missing") . "\n";

// Check position in internal_notes (migration 011)
$positionCol = Capsule::select("SHOW COLUMNS FROM internal_notes WHERE Field = 'position'");
echo "Migration 011 (notes position): " . (count($positionCol) > 0 ? "✅ Applied" : "❌ Missing") . "\n";

// Check archived status (migration 012) - archived is now in status ENUM
$statusType = $columns[0]->Type;
$hasArchived = strpos($statusType, 'archived') !== false;
echo "Migration 012 (archived status): " . ($hasArchived ? "✅ Applied" : "❌ Missing") . "\n";

// Check user settings (migration 013)
$userSettingsCols = Capsule::select("SHOW COLUMNS FROM users WHERE Field IN ('avatar_path', 'timezone', 'language')");
echo "Migration 013 (user settings): " . (count($userSettingsCols) === 3 ? "✅ Applied" : "❌ Missing") . "\n";

// Check signatures table (migration 014)
try {
    $signaturesExists = Capsule::select("SHOW TABLES LIKE 'signatures'");
    echo "Migration 014 (signatures table): " . (count($signaturesExists) > 0 ? "✅ Applied" : "❌ Missing") . "\n";
} catch (\Exception $e) {
    echo "Migration 014 (signatures table): ❌ Error checking\n";
}

echo "\n";
