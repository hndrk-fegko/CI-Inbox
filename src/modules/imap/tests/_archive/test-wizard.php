#!/usr/bin/env php
<?php
/**
 * Non-interactive test for setup-autodiscover.php
 * Simulates user input for automated testing
 */

$inputs = [
    'testuser@localhost',     // Email
    '',                       // IMAP Host (auto-detect)
    '',                       // IMAP Port (auto-detect)
    '',                       // IMAP SSL (auto-detect)
    'testuser',              // IMAP Username
    'testpass123',           // IMAP Password
];

$inputString = implode("\n", $inputs);

passthru(
    'echo "' . $inputString . '" | C:\xampp\php\php.exe src/modules/imap/tests/setup-autodiscover.php',
    $exitCode
);

exit($exitCode);
