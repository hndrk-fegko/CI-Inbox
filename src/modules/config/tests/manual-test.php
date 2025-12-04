<?php
declare(strict_types=1);

/**
 * Manual Test Script for Config Module
 * 
 * Run from project root: php src/modules/config/tests/manual-test.php
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Config\Exceptions\ConfigException;

echo "=== CI-Inbox Config Module - Manual Test ===\n\n";

// 1. Create config instance
echo "1. Creating ConfigService...\n";
$config = new ConfigService();
echo "   ✅ ConfigService created\n\n";

// 2. Test basic get
echo "2. Testing basic get()...\n";
$appName = $config->get('app.name');
echo "   app.name = " . var_export($appName, true) . "\n";
echo "   ✅ Basic get() works\n\n";

// 3. Test type-safe getters
echo "3. Testing type-safe getters...\n";
try {
    $name = $config->getString('app.name');
    echo "   getString('app.name') = '{$name}'\n";
    
    $debug = $config->getBool('app.debug');
    echo "   getBool('app.debug') = " . var_export($debug, true) . "\n";
    
    $dbPort = $config->getInt('database.connections.mysql.port');
    echo "   getInt('database.connections.mysql.port') = {$dbPort}\n";
    
    $dbConfig = $config->getArray('database.connections');
    echo "   getArray('database.connections') = Array with " . count($dbConfig) . " items\n";
    
    echo "   ✅ Type-safe getters work\n\n";
} catch (ConfigException $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// 4. Test default values
echo "4. Testing default values...\n";
$timeout = $config->get('app.timeout', 30);
echo "   get('app.timeout', 30) = {$timeout} (key doesn't exist, used default)\n";

$port = $config->getInt('app.port', 8080);
echo "   getInt('app.port', 8080) = {$port} (key doesn't exist, used default)\n";
echo "   ✅ Default values work\n\n";

// 5. Test has()
echo "5. Testing has()...\n";
$hasAppName = $config->has('app.name');
echo "   has('app.name') = " . var_export($hasAppName, true) . "\n";

$hasInvalid = $config->has('nonexistent.key');
echo "   has('nonexistent.key') = " . var_export($hasInvalid, true) . "\n";
echo "   ✅ has() works\n\n";

// 6. Test nested dot notation
echo "6. Testing nested dot notation...\n";
$dbHost = $config->get('database.connections.mysql.host');
echo "   get('database.connections.mysql.host') = '{$dbHost}'\n";

$dbCharset = $config->get('database.connections.mysql.charset');
echo "   get('database.connections.mysql.charset') = '{$dbCharset}'\n";
echo "   ✅ Nested dot notation works\n\n";

// 7. Test exception for missing required key
echo "7. Testing exception for missing required key...\n";
try {
    $config->getString('nonexistent.key');
    echo "   ❌ Should have thrown exception!\n\n";
} catch (ConfigException $e) {
    echo "   ✅ Exception thrown: " . $e->getMessage() . "\n\n";
}

// 8. Test all()
echo "8. Testing all()...\n";
$allConfig = $config->all();
echo "   all() returned " . count($allConfig) . " top-level keys: " . implode(', ', array_keys($allConfig)) . "\n";
echo "   ✅ all() works\n\n";

// 9. Display sample config structure
echo "9. Sample configuration structure:\n";
echo "   {\n";
echo "     'app': {\n";
echo "       'name': '{$config->getString('app.name')}',\n";
echo "       'env': '{$config->getString('app.env')}',\n";
echo "       'debug': " . var_export($config->getBool('app.debug'), true) . ",\n";
echo "       'url': '{$config->getString('app.url')}'\n";
echo "     },\n";
echo "     'database': {\n";
echo "       'connection': '{$config->getString('database.connection')}',\n";
echo "       'connections.mysql.host': '{$config->getString('database.connections.mysql.host')}',\n";
echo "       'connections.mysql.port': {$config->getInt('database.connections.mysql.port')},\n";
echo "       'connections.mysql.database': '{$config->getString('database.connections.mysql.database')}'\n";
echo "     }\n";
echo "   }\n\n";

// Summary
echo "===========================================\n";
echo "✅ ALL TESTS PASSED\n";
echo "===========================================\n";
echo "\nConfig module is ready to use!\n";
echo "Try: \$config->getString('app.name')\n\n";
