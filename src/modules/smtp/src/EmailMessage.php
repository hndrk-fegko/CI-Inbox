<?php

declare(strict_types=1);

namespace CiInbox\Modules\Smtp;

/**
 * Email Message DTO
 */
class EmailMessage
{
    public function __construct(
        public readonly string $subject,
        public readonly string $bodyText,
        public readonly string $bodyHtml,
        public readonly array $to,           // [['email' => '...', 'name' => '...']]
        public readonly array $cc = [],
        public readonly array $bcc = [],
        public readonly array $replyTo = [],
        public readonly ?string $inReplyTo = null,
        public readonly array $references = [],
        public readonly array $attachments = []
    ) {}
}
