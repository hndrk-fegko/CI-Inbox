<?php

/**
 * Migration: Update thread status enum
 * 
 * Add 'assigned' status for workflow automation.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

// MySQL: Modify ENUM directly - keep 'archived' from migration 012
Capsule::statement("
    ALTER TABLE threads 
    MODIFY COLUMN status ENUM('open', 'assigned', 'pending', 'closed', 'archived') 
    DEFAULT 'open'
");

// Update 'pending' status to 'assigned' for consistency
Capsule::statement("
    UPDATE threads 
    SET status = 'assigned' 
    WHERE status = 'pending'
");
