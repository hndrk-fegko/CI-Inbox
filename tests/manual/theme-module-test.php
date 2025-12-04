<?php
/**
 * Theme Module Manual Test
 * 
 * Tests theme preference storage and retrieval.
 * Usage: php tests/manual/theme-module-test.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Theme\ThemeService;
use CiInbox\App\Models\User;

// Initialize system
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

$logger = new LoggerService(__DIR__ . '/../../logs/');
$themeService = new ThemeService($logger, $config);

echo "=== Theme Module Test ===" . PHP_EOL . PHP_EOL;

// TEST 1: Get default theme for user
echo "TEST 1: Get default theme for user" . PHP_EOL;
try {
    $user = User::first();
    if (!$user) {
        echo "❌ No users found in database. Please seed test data first." . PHP_EOL;
        exit(1);
    }
    
    $themeMode = $themeService->getUserTheme($user->id);
    echo "✅ User {$user->id} theme: {$themeMode}" . PHP_EOL;
    echo "   Expected: 'auto' (default)" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// TEST 2: Set theme to dark
echo "TEST 2: Set theme to dark" . PHP_EOL;
try {
    $success = $themeService->setUserTheme($user->id, 'dark');
    if ($success) {
        $verifyTheme = $themeService->getUserTheme($user->id);
        if ($verifyTheme === 'dark') {
            echo "✅ Theme updated to dark and verified" . PHP_EOL;
        } else {
            echo "❌ Theme not properly saved. Got: {$verifyTheme}" . PHP_EOL;
            exit(1);
        }
    } else {
        echo "❌ Failed to set theme" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// TEST 3: Set theme to light
echo "TEST 3: Set theme to light" . PHP_EOL;
try {
    $success = $themeService->setUserTheme($user->id, 'light');
    if ($success) {
        $verifyTheme = $themeService->getUserTheme($user->id);
        if ($verifyTheme === 'light') {
            echo "✅ Theme updated to light and verified" . PHP_EOL;
        } else {
            echo "❌ Theme not properly saved. Got: {$verifyTheme}" . PHP_EOL;
            exit(1);
        }
    } else {
        echo "❌ Failed to set theme" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// TEST 4: Reset to auto
echo "TEST 4: Reset theme to auto" . PHP_EOL;
try {
    $success = $themeService->setUserTheme($user->id, 'auto');
    if ($success) {
        $verifyTheme = $themeService->getUserTheme($user->id);
        if ($verifyTheme === 'auto') {
            echo "✅ Theme reset to auto and verified" . PHP_EOL;
        } else {
            echo "❌ Theme not properly saved. Got: {$verifyTheme}" . PHP_EOL;
            exit(1);
        }
    } else {
        echo "❌ Failed to set theme" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// TEST 5: Invalid theme mode
echo "TEST 5: Validate invalid theme mode" . PHP_EOL;
try {
    $isValid = $themeService->isValidThemeMode('invalid');
    if (!$isValid) {
        echo "✅ Invalid theme mode correctly rejected" . PHP_EOL;
    } else {
        echo "❌ Invalid theme mode was accepted" . PHP_EOL;
        exit(1);
    }
    
    $success = $themeService->setUserTheme($user->id, 'invalid');
    if (!$success) {
        echo "✅ setUserTheme correctly rejected invalid mode" . PHP_EOL;
    } else {
        echo "❌ setUserTheme accepted invalid mode" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// TEST 6: Get valid themes list
echo "TEST 6: Get valid themes list" . PHP_EOL;
try {
    $validThemes = $themeService->getValidThemes();
    $expected = ['auto', 'light', 'dark'];
    
    if ($validThemes === $expected) {
        echo "✅ Valid themes list correct: " . implode(', ', $validThemes) . PHP_EOL;
    } else {
        echo "❌ Valid themes list incorrect" . PHP_EOL;
        echo "   Expected: " . implode(', ', $expected) . PHP_EOL;
        echo "   Got: " . implode(', ', $validThemes) . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;
echo "=== All Tests Passed ✅ ===" . PHP_EOL;
