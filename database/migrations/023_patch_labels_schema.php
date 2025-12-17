<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('labels')) {
            return;
        }

        $hasIsSystem   = $schema->hasColumn('labels', 'is_system');
        $hasCreatedAt  = $schema->hasColumn('labels', 'created_at');
        $hasUpdatedAt  = $schema->hasColumn('labels', 'updated_at');
        $hasDescription= $schema->hasColumn('labels', 'description');
        $hasColor      = $schema->hasColumn('labels', 'color');

        if (!$hasIsSystem || !$hasCreatedAt || !$hasUpdatedAt) {
            $schema->table('labels', function ($table) use ($hasIsSystem, $hasCreatedAt, $hasUpdatedAt, $hasDescription, $hasColor) {
                if (!$hasIsSystem) {
                    // Add without AFTER; set position only if a safe reference exists
                    $col = $table->boolean('is_system')->default(false);
                    if ($hasDescription) {
                        $col->after('description');
                    } elseif ($hasColor) {
                        $col->after('color');
                    }
                }
                if (!$hasCreatedAt) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!$hasUpdatedAt) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        // Try to add index on is_system (ignore if it already exists)
        try {
            if ($schema->hasColumn('labels', 'is_system')) {
                $schema->table('labels', function ($table) {
                    $table->index('is_system');
                });
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // No down-migration for patch
    }
};