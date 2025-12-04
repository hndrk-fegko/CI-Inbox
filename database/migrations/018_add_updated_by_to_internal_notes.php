<?php

/**
 * Migration: Add updated_by_user_id to internal_notes
 * 
 * Tracks who last edited a note
 */

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        if (!Capsule::schema()->hasColumn('internal_notes', 'updated_by_user_id')) {
            Capsule::schema()->table('internal_notes', function ($table) {
                $table->unsignedBigInteger('updated_by_user_id')->nullable()->after('user_id');
                $table->foreign('updated_by_user_id')->references('id')->on('users')->onDelete('set null');
            });
            
            echo "Added updated_by_user_id column to internal_notes table\n";
        } else {
            echo "Column updated_by_user_id already exists in internal_notes table\n";
        }
    }

    public function down(): void
    {
        if (Capsule::schema()->hasColumn('internal_notes', 'updated_by_user_id')) {
            Capsule::schema()->table('internal_notes', function ($table) {
                $table->dropForeign(['updated_by_user_id']);
                $table->dropColumn('updated_by_user_id');
            });
            
            echo "Dropped updated_by_user_id column from internal_notes table\n";
        }
    }
};
