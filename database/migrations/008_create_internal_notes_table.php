<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        // Skip if table already exists
        if (!Capsule::schema()->hasTable('internal_notes')) {
            return;
        }
        if (Capsule::schema()->hasColumn('internal_notes', 'position')) {
            return; // column already exists, skip
        }

        Capsule::schema()->table('internal_notes', function ($table) {
            $table->integer('position')->nullable()->after('type');
        });
    }
};
