<?php
/**
 * Discussion comment notification for comments created via Next Gen API only.
 */
declare(strict_types=1);

require_once BNOTE_ROOT . '/src/data/modules/startdata.php';
require_once BNOTE_ROOT . '/src/logic/mailrecipientpolicy.php';
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

            /** @var list<array{email:string,firstName:string}> $recipients */
            $recipients = [];
            $seen = [];
            for ($i = 1; $i < count($contacts); $i++) {
                $contact = $contacts[$i];
                if (!$system_data->contactEmailNotificationOn($contact['id'])) {
                    continue;
                }
                $e = trim((string) ($contact['email'] ?? ''));
                if ($e === '' || !filter_var($e, FILTER_VALIDATE_EMAIL) || isset($seen[$e])) {
                    continue;
                }
                if (MailRecipientPolicy::shouldSkipOutboundDelivery($e)) {
                    continue;
                }
                $seen[$e] = true;
                $recipients[] = [
                    'email' => $e,
                    'firstName' => trim((string) ($contact['name'] ?? '')),
                ];
            }
            if (count($recipients) < 1) {
                return;
            }

            $locale = method_exists($system_data, 'getLang') ? (string) ($system_data->getLang() ?: 'en') : 'en';

            $authorLine = '';
            $sender = $system_data->getUsersContact($authorUserId);
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
}
