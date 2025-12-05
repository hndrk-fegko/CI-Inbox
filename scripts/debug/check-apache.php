<?php
/**
 * Apache Module Check
 */

echo "<h1>Apache Module Check</h1>";

// Check if we're running on Apache
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    
    echo "<h2>Loaded Apache Modules:</h2>";
    echo "<ul>";
    foreach ($modules as $module) {
        echo "<li>{$module}</li>";
    }
    echo "</ul>";
    
    // Check specifically for mod_rewrite
    if (in_array('mod_rewrite', $modules)) {
        echo "<p style='color: green; font-weight: bold;'>✅ mod_rewrite is ENABLED</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ mod_rewrite is NOT enabled</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ apache_get_modules() not available. Might not be Apache or running as CGI.</p>";
}

// Check .htaccess is being read
echo "<h2>.htaccess Test:</h2>";
echo "<p>If you can see this page at /check-apache.php, then basic PHP works.</p>";
echo "<p>Try accessing: <a href='/test-rewrite'>/test-rewrite</a></p>";
echo "<p>If .htaccess works, that link should show this page via index.php routing.</p>";

// Check REQUEST_URI
echo "<h2>Request Info:</h2>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "</pre>";
