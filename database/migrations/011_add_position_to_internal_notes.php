<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        Capsule::schema()->table('internal_notes', function ($table) {
            $table->integer('position')->nullable()->after('type');
            $table->index('position');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('internal_notes', function ($table) {
            $table->dropColumn('position');
        });
    }
};
