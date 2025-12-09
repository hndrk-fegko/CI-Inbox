<?php
/**
 * CI-Inbox Installation Router
 * 
 * This file serves as the entry point during installation.
 * It checks if the application is installed and routes accordingly.
 * 
 * Lifecycle:
 * 1. Created by repository (this file)
 * 2. Routes to installer if .env is missing
 * 3. Deleted by installer after successful installation (Step 7)
 * 4. After deletion, .htaccess takes over routing
 * 
 * IMPORTANT: This file is automatically deleted after installation completes.
 * If you see this file in a production environment, the installation was interrupted.
 */

// Determine project root (this file is in root)
define('PROJECT_ROOT', __DIR__);

// Check if installation is complete
$envExists = file_exists(PROJECT_ROOT . '/.env');
$vendorExists = file_exists(PROJECT_ROOT . '/vendor/autoload.php');

// Installation complete when both .env and vendor exist
$installationComplete = $envExists && $vendorExists;

if (!$installationComplete) {
    // Installation not complete - redirect to setup wizard
    header('Location: /src/public/setup/');
    exit;
}

// If we reach here, installation is complete but this file wasn't deleted
// This happens when:
// 1. User interrupted installation after .env creation but before cleanup
// 2. Installer re-run to fix incomplete installation

// Check if setup wizard still exists and is accessible
$setupExists = file_exists(PROJECT_ROOT . '/src/public/setup/index.php');

if ($setupExists) {
    // Setup still exists - let installer complete and delete this file
    header('Location: /src/public/setup/');
    exit;
}

// Setup was deleted but this file remains - redirect to application
// (This shouldn't normally happen, but handle it gracefully)
header('Location: /src/public/');
exit;
