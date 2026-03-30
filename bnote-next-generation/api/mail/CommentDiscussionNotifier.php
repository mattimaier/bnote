<?php
/**
 * Discussion comment notification for comments created via Next Gen API only.
 */
declare(strict_types=1);

require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once __DIR__ . '/MailRecipientPolicy.php';
require_once __DIR__ . '/NextGenMailPolicy.php';
require_once __DIR__ . '/NextGenMailer.php';
require_once __DIR__ . '/builders/CommentDiscussionMailBuilder.php';
require_once __DIR__ . '/CommentDiscussionEntitySummary.php';

final class CommentDiscussionNotifier {
    /**
     * @param StartData $startData
     */
    public static function sendSafe($system_data, $startData, string $otype, int $oid, int $authorUserId): void {
        try {
            if (!NextGenMailPolicy::shouldSendPublicMail($system_data)) {
                return;
            }
            $otype = strtoupper($otype);
            if (!in_array($otype, ['R', 'C', 'V'], true) || $oid < 1) {
                return;
            }

            $contacts = $startData->getContactsForObject($otype, $oid);
            if ($contacts === null || count($contacts) <= 1) {
                return;
            }

            $authorContactId = 0;
            $sender = $system_data->getUsersContact($authorUserId);
            if (is_array($sender)) {
                $authorContactId = (int) ($sender['id'] ?? 0);
            }

            /** @var list<array{email:string,firstName:string}> $recipients */
            $recipients = [];
            $seen = [];
            for ($i = 1; $i < count($contacts); $i++) {
                $contact = $contacts[$i];
                $cid = self::contactRowId($contact);
                if ($authorContactId > 0 && $cid === $authorContactId) {
                    continue;
                }
                $e = trim((string) ($contact['email'] ?? ''));
                if ($e === '' || !filter_var($e, FILTER_VALIDATE_EMAIL) || isset($seen[$e])) {
                    continue;
                }
                if (!self::shouldNotifyContactForDiscussion($system_data, $cid, $e)) {
                    continue;
                }
                if (MailRecipientPolicy::shouldSkipOutboundDelivery($e)) {
                    continue;
                }
                $seen[$e] = true;
                $recipients[] = [
                    'email' => $e,
                    'firstName' => self::contactRowSalutationName($contact),
                ];
            }
            if (count($recipients) < 1) {
                return;
            }

            $locale = method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';

            $authorLine = '';
            if (is_array($sender)) {
                $authorLine = trim(($sender['name'] ?? '') . ' ' . ($sender['surname'] ?? ''));
            }

            $entityTitle = $startData->getObjectTitle($otype, $oid);
            if (!is_string($entityTitle)) {
                $entityTitle = '';
            }

            $entityCard = CommentDiscussionEntitySummary::load($otype, $oid, $system_data);

            $adp = $startData->adp();
            $rows = $adp->getDiscussion($otype, $oid);
            $thread = self::buildThreadChronological($rows, CommentDiscussionMailBuilder::threadMax());

            $messages = [];
            foreach ($recipients as $r) {
                $messages[] = CommentDiscussionMailBuilder::build(
                    $system_data,
                    $locale,
                    $otype,
                    $oid,
                    $entityTitle,
                    $authorLine,
                    $thread,
                    $entityCard,
                    [$r['email']],
                    [],
                    $r['firstName']
                );
            }
            NextGenMailer::sendBulk($messages);
        } catch (Throwable $e) {
            error_log('CommentDiscussionNotifier: ' . $e->getMessage());
        }
    }

    /**
     * @param mixed $rows getDiscussion result (row 0 = header)
     * @return list<array{author:string,message:string,created_at:string,is_new:bool}>
     */
    private static function buildThreadChronological($rows, int $max): array {
        if (!is_array($rows) || count($rows) <= 1) {
            return [];
        }
        $list = [];
        for ($i = 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            $list[] = [
                'author' => isset($r['author']) ? (string) $r['author'] : '',
                'message' => isset($r['message']) ? urldecode((string) $r['message']) : '',
                'created_at' => isset($r['created_at']) ? (string) $r['created_at'] : '',
                'is_new' => false,
            ];
        }
        // Rows are newest-first; reverse to chronological
        $list = array_reverse($list);
        if (count($list) > $max) {
            $list = array_slice($list, -$max);
        }
        $n = count($list);
        if ($n > 0) {
            $list[$n - 1]['is_new'] = true;
        }

        return $list;
    }

    /**
     * Same rules as {@see NextGenMailPolicy::contactTransactionalMailDenyReason}, plus email/contact validation
     * for this notifier’s describeRecipients output.
     *
     * @param mixed $system_data Systemdata
     * @return null|string null = would notify; otherwise a machine reason for describeRecipients
     */
    private static function contactDiscussionDenyReason($system_data, int $contactId, string $email): ?string {
        if ($contactId < 1 || $email === '') {
            return 'invalid_contact_or_email';
        }
        if (!isset($system_data->dbcon)) {
            return 'no_db';
        }

        return NextGenMailPolicy::contactTransactionalMailDenyReason($system_data, $contactId);
    }

    /**
     * @param mixed $system_data Systemdata
     */
    private static function shouldNotifyContactForDiscussion($system_data, int $contactId, string $email): bool {
        return self::contactDiscussionDenyReason($system_data, $contactId, $email) === null;
    }

    /** @param array<string,mixed> $contact */
    private static function contactRowId(array $contact): int {
        foreach (['id', 'Id', 'ID'] as $k) {
            if (isset($contact[$k]) && $contact[$k] !== '' && $contact[$k] !== null) {
                return (int) $contact[$k];
            }
        }

        return 0;
    }

    /** @param array<string,mixed> $contact */
    private static function contactRowSalutationName(array $contact): string {
        $n = trim((string) ($contact['name'] ?? $contact['fullname'] ?? $contact['Fullname'] ?? ''));

        return $n;
    }

    /**
     * Local debug: who would receive (loopback script). Same rules as sendSafe.
     *
     * @return array<string,mixed>
     */
    public static function describeRecipients($system_data, $startData, string $otype, int $oid, int $authorUserId = 0): array {
        $otype = strtoupper($otype);
        $out = [
            'otype' => $otype,
            'oid' => $oid,
            'would_send_if_comment_added' => NextGenMailPolicy::shouldSendPublicMail($system_data),
            'rows' => [],
            'recipients_emails' => [],
        ];
        if (!in_array($otype, ['R', 'C', 'V'], true) || $oid < 1) {
            $out['error'] = 'invalid otype or oid';

            return $out;
        }
        $contacts = $startData->getContactsForObject($otype, $oid);
        if ($contacts === null) {
            $out['note'] = 'getContactsForObject returned null';

            return $out;
        }
        $out['raw_row_count_including_header'] = count($contacts);
        if (count($contacts) <= 1) {
            $out['note'] = 'no contact rows';

            return $out;
        }
        $authorContactId = 0;
        if ($authorUserId > 0) {
            $sender = $system_data->getUsersContact($authorUserId);
            if (is_array($sender)) {
                $authorContactId = (int) ($sender['id'] ?? 0);
            }
        }
        $seen = [];
        for ($i = 1; $i < count($contacts); $i++) {
            $contact = $contacts[$i];
            $cid = self::contactRowId($contact);
            $e = trim((string) ($contact['email'] ?? ''));
            $reasons = [];
            $skipAuthor = $authorContactId > 0 && $cid === $authorContactId;
            if ($skipAuthor) {
                $reasons[] = 'skipped_author';
            }
            $validEmail = $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL);
            if (!$validEmail) {
                $reasons[] = 'invalid_or_empty_email';
            }
            $policyOk = $validEmail && !MailRecipientPolicy::shouldSkipOutboundDelivery($e);
            if ($validEmail && !$policyOk) {
                $reasons[] = 'mail_recipient_policy';
            }
            $deny = ($cid >= 1 && $validEmail) ? self::contactDiscussionDenyReason($system_data, $cid, $e) : 'invalid_contact_or_email';
            $gateOk = $deny === null;
            if (!$skipAuthor && $validEmail && $policyOk && !$gateOk && $deny !== null) {
                $reasons[] = $deny;
            }
            $would = !$skipAuthor && $validEmail && $policyOk && $gateOk && !isset($seen[strtolower($e)]);
            if ($would) {
                $seen[strtolower($e)] = true;
                $out['recipients_emails'][] = $e;
            }
            $out['rows'][] = [
                'contact_id' => $cid,
                'email' => $e,
                'name' => self::contactRowSalutationName($contact),
                'would_receive' => $would,
                'reasons' => $reasons,
            ];
        }

        return $out;
    }
}
