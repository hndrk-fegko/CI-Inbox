<?php
/**
 * Setup Wizard - AJAX Handlers
 * 
 * Handles AJAX requests for connection testing and autodiscovery
 */

declare(strict_types=1);

/**
 * Test IMAP connection with automatic SSL certificate hostname detection
 * 
 * @param string $host IMAP hostname
 * @param int $port IMAP port (993 or 143)
 * @param bool $ssl Use SSL connection
 * @param string $username Username (usually email)
 * @param string $password Password
 * @return array Result with success status and connection details
 */
function testImapConnection(string $host, int $port, bool $ssl, string $username, string $password): array
{
    try {
        // If SSL, check certificate first to get real hostname
        if ($ssl) {
            $realHost = extractRealHostFromCertError($host, $port);
            if ($realHost && $realHost !== $host) {
                // Certificate says different hostname - use that one
                $originalHost = $host;
                $host = $realHost;
                $certificateFix = true;
            }
        }

        $connectionString = $ssl
            ? "{{$host}:{$port}/imap/ssl/novalidate-cert}INBOX"
            : "{{$host}:{$port}/imap/notls}INBOX";

        $imap = @imap_open($connectionString, $username, $password);

        if (!$imap) {
            $error = imap_last_error() ?: 'Connection failed';
            return ['success' => false, 'error' => $error];
        }

        imap_close($imap);

        $result = [
            'success' => true,
            'connection_string' => $connectionString,
            'host' => $host,
            'port' => $port,
            'ssl' => $ssl,
            'username' => $username
        ];

        if (isset($certificateFix) && isset($originalHost)) {
            $result['certificate_fix'] = true;
            $result['original_host'] = $originalHost;
        }

        return $result;

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Test SMTP connection with port-specific handling
 * 
 * @param string $host SMTP hostname
 * @param int $port SMTP port (25, 587, or 465)
 * @param bool $ssl Use SSL connection (port 465)
 * @return array Result with success status
 */
function testSmtpConnection(string $host, int $port, bool $ssl): array
{
    try {
        $errno = 0;
        $errstr = '';
        
        // Port 465: Direct SSL connection
        if ($port == 465) {
            $socket = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                10
            );
        }
        // Port 587/25: Plain connection (STARTTLS would be negotiated later)
        else {
            $socket = @stream_socket_client(
                "tcp://{$host}:{$port}",
                $errno,
                $errstr,
                10
            );
        }

        if (!$socket) {
            return ['success' => false, 'error' => "Connection failed: ({$errno}) {$errstr}"];
        }

        // Read server greeting
        $response = fgets($socket);
        fclose($socket);

        if (strpos($response, '220') === 0) {
            return ['success' => true, 'message' => 'SMTP connection successful'];
        }

        return ['success' => false, 'error' => 'Invalid SMTP response: ' . $response];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Handle AJAX requests
 * Routes AJAX requests to appropriate handlers
 * 
 * @return void Outputs JSON and exits
 */
function handleAjaxRequest(): void
{
    if (!isset($_GET['ajax'])) {
        return;
    }
    
    header('Content-Type: application/json');

    switch ($_GET['ajax']) {
        case 'test_imap':
            handleImapTestAjax();
            break;

        case 'test_smtp':
            handleSmtpTestAjax();
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown AJAX action']);
            exit;
    }
}

/**
 * Handle IMAP test AJAX request
 * Tests IMAP connection with autodiscovery
 */
function handleImapTestAjax(): void
{
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Email and password required']);
        exit;
    }

    // Get autodiscover candidates
    $hosts = autoDetectHosts($email);
    
    // Test IMAP candidates
    foreach ($hosts['imap_candidates'] as $host) {
        // Try SSL first (port 993)
        $result = testImapConnection($host, 993, true, $email, $password);
        if ($result['success']) {
            echo json_encode($result);
            exit;
        }
        
        // Try non-SSL (port 143)
        $result = testImapConnection($host, 143, false, $email, $password);
        if ($result['success']) {
            echo json_encode($result);
            exit;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Could not connect to any IMAP server']);
    exit;
}

/**
 * Handle SMTP test AJAX request
 * Tests SMTP connection
 */
function handleSmtpTestAjax(): void
{
    $host = $_POST['host'] ?? '';
    $port = (int)($_POST['port'] ?? 587);
    $ssl = ($_POST['ssl'] ?? 'false') === 'true';

    if (empty($host)) {
        echo json_encode(['success' => false, 'error' => 'SMTP host required']);
        exit;
    }

    $result = testSmtpConnection($host, $port, $ssl);
    echo json_encode($result);
    exit;
}
