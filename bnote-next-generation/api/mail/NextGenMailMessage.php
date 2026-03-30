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
    /** @var list<array{path: string, cid: string}> */
    public array $embeds = [];

    /**
     * @param list<string> $to
     * @param list<string> $bcc
     * @param list<array{path: string, cid: string}> $embeds
     */
    public function __construct(
        array $to = [],
        array $bcc = [],
        string $subject = '',
        string $htmlBody = '',
        string $textBody = '',
        string $templateId = '',
        array $embeds = []
    ) {
        $this->to = $to;
        $this->bcc = $bcc;
        $this->subject = $subject;
        $this->htmlBody = $htmlBody;
        $this->textBody = $textBody;
        $this->templateId = $templateId;
        $this->embeds = $embeds;
    }
}
