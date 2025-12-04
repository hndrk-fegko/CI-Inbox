<?php

namespace CiInbox\Modules\Imap\Manager;

use CiInbox\Modules\Imap\Parser\ParsedEmail;
use CiInbox\Modules\Logger\LoggerService;

/**
 * Thread Manager Interface
 * 
 * Manages email threading based on Message-ID, In-Reply-To, and References headers.
 */
interface ThreadManagerInterface
{
    /**
     * Build threads from emails based on Message-ID, In-Reply-To, References
     * 
     * @param array $emails Array of ParsedEmail objects
     * @return array Array of ThreadStructure objects
     */
    public function buildThreads(array $emails): array;
    
    /**
     * Find thread for a single email
     * 
     * @param ParsedEmail $email
     * @param array $existingThreads Optional existing threads to check
     * @return ?string Thread ID (or null if new thread)
     */
    public function findThreadForEmail(ParsedEmail $email, array $existingThreads = []): ?string;
}
