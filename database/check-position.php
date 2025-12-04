<?php
require 'vendor/autoload.php';

use CiInbox\Core\Container;
use Illuminate\Database\Capsule\Manager as Capsule;

$container = Container::getInstance();
$container->get('database');

$columns = Capsule::schema()->getColumnListing('internal_notes');
echo "Columns in internal_notes table:\n";
print_r($columns);

if (in_array('position', $columns)) {
    echo "\n✅ Position column already exists\n";
} else {
    echo "\n❌ Position column missing - running migration...\n";
    
    // Run migration
    Capsule::schema()->table('internal_notes', function ($table) {
        $table->integer('position')->nullable()->after('type');
        $table->index('position');
    });
    
    echo "✅ Position column added successfully\n";
}
