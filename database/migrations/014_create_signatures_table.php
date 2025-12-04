<?php

/**
 * Migration: Create signatures table
 * 
 * Stores email signatures (global for system, personal for users)
 */

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        DB::schema()->create('signatures', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('type', ['global', 'personal'])->default('personal');
            $table->string('name', 255);
            $table->text('content');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            // Foreign key
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            
            // Indexes
            $table->index('user_id');
            $table->index('type');
            $table->index(['user_id', 'is_default']);
        });
        
        echo "✓ Table 'signatures' created\n";
    }
    
    public function down(): void
    {
        DB::schema()->dropIfExists('signatures');
        echo "✓ Table 'signatures' dropped\n";
    }
};
