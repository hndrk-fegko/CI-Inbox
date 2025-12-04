<?php

declare(strict_types=1);

namespace CiInbox\Modules\Imap;

use CiInbox\Modules\Imap\Exceptions\ImapException;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Config\ConfigService;

/**
 * IMAP Client
 * 
 * Wraps php-imap extension with clean OOP interface.
 * Handles connection, folder operations, and message retrieval.
 */
class ImapClient implements ImapClientInterface
{
    /** @var resource|false|null IMAP connection resource */
    private $connection = null;

    /** @var string|null Current selected folder */
    private ?string $currentFolder = null;

    /** @var string|null Last error message */
    private ?string $lastError = null;

    /** @var string Connection string (for reconnection) */
    private string $connectionString = '';

    /** @var string Username (for reconnection) */
    private string $username = '';

    /** @var string Password (for reconnection) */
    private string $password = '';

    public function __construct(
        private LoggerService $logger,
        private ConfigService $config
    ) {
        // Check if IMAP extension is available
        if (!extension_loaded('imap')) {
            throw ImapException::extensionNotAvailable();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function connect(
        string $host,
        int $port,
        string $username,
        string $password,
        bool $ssl = true
    ): bool {
        $this->logger->info('Connecting to IMAP server', [
            'host' => $host,
            'port' => $port,
            'ssl' => $ssl
        ]);

        // Build connection string
        $protocol = $ssl ? 'imap' : 'imap';
        $flags = $ssl ? '/imap/ssl/novalidate-cert' : '/imap';
        
        $this->connectionString = "{{$host}:{$port}{$flags}}";
        $this->username = $username;
        $this->password = $password;

        // Attempt connection
        try {
            $this->connection = @imap_open(
                $this->connectionString,
                $username,
                $password
            );

            if ($this->connection === false) {
                $error = $this->getImapLastError();
                $this->logger->error('IMAP connection failed', [
                    'host' => $host,
                    'error' => $error
                ]);
                throw ImapException::connectionFailed($host, $port, $error);
            }

            $this->logger->info('Connected to IMAP server', [
                'host' => $host,
                'port' => $port
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('IMAP connection failed', [
                'host' => $host,
                'exception' => $e->getMessage()
            ]);
            throw ImapException::connectionFailed($host, $port, $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            @imap_close($this->connection);
            $this->connection = null;
            $this->currentFolder = null;
            $this->logger->info('Disconnected from IMAP server');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        return $this->connection !== null && $this->connection !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFolders(): array
    {
        $this->ensureConnected();

        $this->logger->debug('Fetching IMAP folders');

        $folders = @imap_list($this->connection, $this->connectionString, '*');

        if ($folders === false) {
            $error = $this->getImapLastError();
            $this->logger->error('Failed to fetch folders', ['error' => $error]);
            throw ImapException::operationFailed('getFolders', $error);
        }

        // Extract folder names (remove server prefix)
        $folderNames = [];
        foreach ($folders as $folder) {
            // Remove server prefix: {host:port}FolderName -> FolderName
            $folderName = mb_convert_encoding($folder, 'UTF-8', 'UTF7-IMAP');
            $folderName = str_replace($this->connectionString, '', $folderName);
            $folderNames[] = $folderName;
        }

        $this->logger->debug('Folders fetched', [
            'count' => count($folderNames),
            'folders' => $folderNames
        ]);

        return $folderNames;
    }

    /**
     * {@inheritdoc}
     */
    public function selectFolder(string $folder): void
    {
        $this->ensureConnected();

        $this->logger->debug('Selecting folder', ['folder' => $folder]);

        // UTF-7 encode folder name for IMAP
        $folderEncoded = mb_convert_encoding($folder, 'UTF7-IMAP', 'UTF-8');
        $fullPath = $this->connectionString . $folderEncoded;

        $result = @imap_reopen($this->connection, $fullPath);

        if ($result === false) {
            $error = $this->getImapLastError();
            $this->logger->error('Failed to select folder', [
                'folder' => $folder,
                'error' => $error
            ]);
            throw ImapException::folderNotFound($folder);
        }

        $this->currentFolder = $folder;
        $this->logger->debug('Folder selected', ['folder' => $folder]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentFolder(): ?string
    {
        return $this->currentFolder;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageCount(): int
    {
        $this->ensureFolderSelected();

        $check = @imap_check($this->connection);

        if ($check === false) {
            $error = $this->getImapLastError();
            $this->logger->error('Failed to get message count', ['error' => $error]);
            return 0;
        }

        return $check->Nmsgs ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages(int $limit = 100, bool $unreadOnly = false): array
    {
        $this->ensureFolderSelected();

        $this->logger->debug('Fetching messages', [
            'limit' => $limit,
            'unread_only' => $unreadOnly
        ]);

        $messageCount = $this->getMessageCount();

        if ($messageCount === 0) {
            return [];
        }

        // Calculate range (most recent first)
        $start = max(1, $messageCount - $limit + 1);
        $end = $messageCount;

        // Get UIDs
        $uids = @imap_fetch_overview($this->connection, "{$start}:{$end}", 0);

        if ($uids === false) {
            $error = $this->getImapLastError();
            $this->logger->error('Failed to fetch message overview', ['error' => $error]);
            return [];
        }

        $messages = [];
        foreach (array_reverse($uids) as $overview) {
            // Skip if unreadOnly and message is read
            if ($unreadOnly && isset($overview->seen) && $overview->seen == 1) {
                continue;
            }

            try {
                $messages[] = $this->getMessage((string)$overview->uid);
            } catch (ImapException $e) {
                $this->logger->warning('Failed to fetch message', [
                    'uid' => $overview->uid,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->debug('Messages fetched', ['count' => count($messages)]);

        return $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(string $uid): ImapMessageInterface
    {
        $this->ensureFolderSelected();

        $msgNo = @imap_msgno($this->connection, (int)$uid);

        if ($msgNo === false || $msgNo === 0) {
            throw ImapException::messageNotFound($uid);
        }

        return new ImapMessage($this->connection, $uid, $msgNo, $this->logger);
    }

    /**
     * {@inheritdoc}
     */
    public function moveMessage(string $uid, string $targetFolder): bool
    {
        $this->ensureFolderSelected();

        $this->logger->debug('Moving message', [
            'uid' => $uid,
            'target_folder' => $targetFolder
        ]);

        $msgNo = @imap_msgno($this->connection, (int)$uid);

        if ($msgNo === false || $msgNo === 0) {
            throw ImapException::messageNotFound($uid);
        }

        // UTF-7 encode target folder
        $targetFolderEncoded = mb_convert_encoding($targetFolder, 'UTF7-IMAP', 'UTF-8');

        $result = @imap_mail_move($this->connection, (string)$msgNo, $targetFolderEncoded);

        if ($result === false) {
            $error = $this->getImapLastError();
            $this->logger->error('Failed to move message', [
                'uid' => $uid,
                'error' => $error
            ]);
            throw ImapException::operationFailed('moveMessage', $error);
        }

        // Expunge to actually move
        @imap_expunge($this->connection);

        $this->logger->debug('Message moved', [
            'uid' => $uid,
            'target_folder' => $targetFolder
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMessage(string $uid): bool
    {
        $this->ensureFolderSelected();

        $this->logger->debug('Deleting message', ['uid' => $uid]);

        $msgNo = @imap_msgno($this->connection, (int)$uid);

        if ($msgNo === false || $msgNo === 0) {
            throw ImapException::messageNotFound($uid);
        }

        $result = @imap_delete($this->connection, (string)$msgNo);

        if ($result === false) {
            $error = $this->getImapLastError();
            $this->logger->error('Failed to delete message', [
                'uid' => $uid,
                'error' => $error
            ]);
            throw ImapException::operationFailed('deleteMessage', $error);
        }

        // Expunge to actually delete
        @imap_expunge($this->connection);

        $this->logger->debug('Message deleted', ['uid' => $uid]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsRead(string $uid): bool
    {
        return $this->setFlag($uid, '\\Seen');
    }

    /**
     * {@inheritdoc}
     */
    public function markAsUnread(string $uid): bool
    {
        return $this->clearFlag($uid, '\\Seen');
    }

    /**
     * {@inheritdoc}
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Set IMAP flag on message
     * 
     * @param string $uid
     * @param string $flag
     * @return bool
     */
    private function setFlag(string $uid, string $flag): bool
    {
        $this->ensureFolderSelected();

        $msgNo = @imap_msgno($this->connection, (int)$uid);

        if ($msgNo === false || $msgNo === 0) {
            throw ImapException::messageNotFound($uid);
        }

        $result = @imap_setflag_full($this->connection, (string)$msgNo, $flag);

        if ($result === false) {
            $error = $this->getImapLastError();
            $this->logger->error('Failed to set flag', [
                'uid' => $uid,
                'flag' => $flag,
                'error' => $error
            ]);
            return false;
        }

        return true;
    }

    /**
     * Clear IMAP flag on message
     * 
     * @param string $uid
     * @param string $flag
     * @return bool
     */
    private function clearFlag(string $uid, string $flag): bool
    {
        $this->ensureFolderSelected();

        $msgNo = @imap_msgno($this->connection, (int)$uid);

        if ($msgNo === false || $msgNo === 0) {
            throw ImapException::messageNotFound($uid);
        }

        $result = @imap_clearflag_full($this->connection, (string)$msgNo, $flag);

        if ($result === false) {
            $error = $this->getImapLastError();
            $this->logger->error('Failed to clear flag', [
                'uid' => $uid,
                'flag' => $flag,
                'error' => $error
            ]);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $criteria): array
    {
        $this->ensureFolderSelected();

        $this->logger->debug('Searching messages', ['criteria' => $criteria]);

        // IMAP search uses message numbers, we need UIDs
        $messageNumbers = @imap_search($this->connection, $criteria, SE_UID);

        if ($messageNumbers === false) {
            // No matches is not an error
            $this->logger->debug('No messages found for search criteria', ['criteria' => $criteria]);
            return [];
        }

        $this->logger->debug('Search completed', [
            'criteria' => $criteria,
            'count' => count($messageNumbers)
        ]);

        return array_map('strval', $messageNumbers);
    }

    /**
     * {@inheritdoc}
     */
    public function addKeyword(string $uid, string $keyword): bool
    {
        try {
            $result = $this->setFlag($uid, $keyword);
            
            if ($result) {
                $this->logger->debug('Keyword added', [
                    'uid' => $uid,
                    'keyword' => $keyword
                ]);
            }
            
            return $result;
        } catch (\Throwable $e) {
            // Some IMAP servers don't support keywords
            $this->logger->warning('Failed to add keyword (server may not support keywords)', [
                'uid' => $uid,
                'keyword' => $keyword,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeKeyword(string $uid, string $keyword): bool
    {
        try {
            $result = $this->clearFlag($uid, $keyword);
            
            if ($result) {
                $this->logger->debug('Keyword removed', [
                    'uid' => $uid,
                    'keyword' => $keyword
                ]);
            }
            
            return $result;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to remove keyword', [
                'uid' => $uid,
                'keyword' => $keyword,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getKeywords(string $uid): array
    {
        $this->ensureFolderSelected();

        $msgNo = @imap_msgno($this->connection, (int)$uid);

        if ($msgNo === false || $msgNo === 0) {
            throw ImapException::messageNotFound($uid);
        }

        $header = @imap_headerinfo($this->connection, $msgNo);

        if ($header === false) {
            return [];
        }

        // Parse keywords from header
        $keywords = [];
        
        // Standard flags
        if (isset($header->Unseen) && $header->Unseen === 'U') {
            $keywords[] = '\Seen';
        }
        if (isset($header->Answered) && $header->Answered === 'A') {
            $keywords[] = '\Answered';
        }
        if (isset($header->Flagged) && $header->Flagged === 'F') {
            $keywords[] = '\Flagged';
        }
        if (isset($header->Deleted) && $header->Deleted === 'D') {
            $keywords[] = '\Deleted';
        }
        if (isset($header->Draft) && $header->Draft === 'X') {
            $keywords[] = '\Draft';
        }

        // Custom keywords (if available in Recent field)
        if (isset($header->Recent) && is_string($header->Recent)) {
            $customKeywords = explode(' ', trim($header->Recent));
            foreach ($customKeywords as $kw) {
                if (!empty($kw) && !in_array($kw, $keywords)) {
                    $keywords[] = $kw;
                }
            }
        }

        return $keywords;
    }

    /**
     * Ensure connected to IMAP server
     * 
     * @return void
     * @throws ImapException
     */
    private function ensureConnected(): void
    {
        if (!$this->isConnected()) {
            throw ImapException::notConnected();
        }
    }

    /**
     * Ensure folder is selected
     * 
     * @return void
     * @throws ImapException
     */
    private function ensureFolderSelected(): void
    {
        $this->ensureConnected();

        if ($this->currentFolder === null) {
            throw ImapException::noFolderSelected();
        }
    }

    /**
     * Get last IMAP error
     * 
     * @return string
     */
    private function getImapLastError(): string
    {
        $errors = imap_errors();
        $this->lastError = $errors ? implode(', ', $errors) : 'Unknown error';
        return $this->lastError;
    }

    /**
     * Destructor - close connection
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
