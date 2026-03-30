<?php
declare(strict_types=1);

final class NextGenMailMessage {
    /** @var list<string> */
    public array $to = [];
    /** @var list<string> */
    public array $bcc = [];
    public string $subject = '';
    public string $htmlBody = '';
    public string $textBody = '';
    /** For logging only */
    public string $templateId = '';

    /**
     * @param list<string> $to
     * @param list<string> $bcc
     */
    public function __construct(
        array $to = [],
        array $bcc = [],
        string $subject = '',
        string $htmlBody = '',
        string $textBody = '',
        string $templateId = ''
    ) {
        $this->to = $to;
        $this->bcc = $bcc;
        $this->subject = $subject;
        $this->htmlBody = $htmlBody;
        $this->textBody = $textBody;
        $this->templateId = $templateId;
    }
}
