<?php
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

        $needIsSystem  = !$schema->hasColumn('labels', 'is_system');
        $needCreatedAt = !$schema->hasColumn('labels', 'created_at');
        $needUpdatedAt = !$schema->hasColumn('labels', 'updated_at');

        if ($needIsSystem || $needCreatedAt || $needUpdatedAt) {
            $schema->table('labels', function ($table) use ($needIsSystem, $needCreatedAt, $needUpdatedAt) {
                if ($needIsSystem) {
                    $table->boolean('is_system')->default(false)->after('description');
                }
                if ($needCreatedAt) {
                    $table->timestamp('created_at')->nullable();
                }
                if ($needUpdatedAt) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        // Try to add index on is_system (skip if it already exists)
        try {
            if ($schema->hasColumn('labels', 'is_system')) {
                $schema->table('labels', function ($table) {
                    $table->index('is_system');
                });
            }
        } catch (Throwable $e) {
            // ignore if index already exists
        }
    }

    public function down(): void
    {
        // No down-migration for a patch
    }
};