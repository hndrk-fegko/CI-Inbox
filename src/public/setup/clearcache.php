<?php

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ Zend OPcache has been cleared.<br>";
} else {
    echo "❌ Zend OPcache is not configured or the opcache_reset() function is disabled.<br>";
}

if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "✅ APCu cache has been cleared.<br>";
}

echo "<br>Please try the setup process again.";

// For security, this file will attempt to delete itself after running.
//@unlink(__FILE__);

