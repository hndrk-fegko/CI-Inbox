<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        // Skip if table already exists
        if (Capsule::schema()->hasTable('internal_notes')) {
            return;
        }

        Capsule::schema()->create('internal_notes', function ($table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('threads')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('content');
            $table->enum('type', ['user', 'system'])->default('user');
            $table->timestamps();

            $table->index(['thread_id', 'user_id']);
        });
    }
};
