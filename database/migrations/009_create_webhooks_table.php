<?php

/**
 * Migration 009: Create Webhooks Tables
 * 
 * Creates webhooks and webhook_deliveries tables for event notification system.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

class CreateWebhooksTables
{
    public function up(): void
    {
        echo "Creating webhooks table..." . PHP_EOL;
        
        DB::schema()->create('webhooks', function ($table) {
            $table->bigIncrements('id');
            $table->string('url', 500);
            $table->json('events'); // ['thread.created', 'email.sent']
            $table->string('secret', 255); // HMAC secret
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->timestamps();
            
            $table->index('is_active');
        });
        
        echo "✅ webhooks table created" . PHP_EOL;
        
        echo "Creating webhook_deliveries table..." . PHP_EOL;
        
        DB::schema()->create('webhook_deliveries', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('webhook_id');
            $table->string('event_type', 100);
            $table->json('payload');
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('attempts')->default(1);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            $table->foreign('webhook_id')
                  ->references('id')
                  ->on('webhooks')
                  ->onDelete('cascade');
            
            $table->index('webhook_id');
            $table->index('event_type');
            $table->index('created_at');
        });
        
        echo "✅ webhook_deliveries table created" . PHP_EOL;
    }
    
    public function down(): void
    {
        echo "Dropping webhook_deliveries table..." . PHP_EOL;
        DB::schema()->dropIfExists('webhook_deliveries');
        
        echo "Dropping webhooks table..." . PHP_EOL;
        DB::schema()->dropIfExists('webhooks');
        
        echo "✅ Tables dropped" . PHP_EOL;
    }
}

// Run migration if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "=== Migration 009: Create Webhooks Tables ===" . PHP_EOL . PHP_EOL;
    
    // Initialize database
    $config = new CiInbox\Modules\Config\ConfigService(__DIR__ . '/../../');
    require_once __DIR__ . '/../../src/bootstrap/database.php';
    initDatabase($config);
    
    // Check if tables already exist
    if (DB::schema()->hasTable('webhooks')) {
        echo "⚠️  Table 'webhooks' already exists!" . PHP_EOL;
        echo "Run down() to drop tables first." . PHP_EOL;
        exit(1);
    }
    
    // Run migration
    $migration = new CreateWebhooksTables();
    $migration->up();
    
    echo PHP_EOL . "✅ Migration completed successfully" . PHP_EOL;
}
