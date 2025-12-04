<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        Capsule::schema()->create('internal_notes', function ($table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('threads')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('content');
            $table->enum('type', ['user', 'system'])->default('user');
            $table->timestamps();
            
            $table->index('thread_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('internal_notes');
    }
};
